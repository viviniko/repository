<?php

namespace Viviniko\Repository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BuilderFactory
{
    /**
     * Create a new search builder instance.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $builder
     * @param  string|array  $query
     * @param  array  $rules
     *
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public static function make($builder, $query, $rules)
    {
        $rules = static::parseRules($rules);

        if (!empty($query) && is_array($query)) {
            foreach ($query as $name => $value) {
                if ($value != null && trim($value) != '' && isset($rules[$name])) {
                    $condition = $rules[$name];
                    if (is_string($condition)) {
                        $condition = [[$name, $condition]];
                    }

                    if (is_array($condition)) {
                        $i = 0;
                        $column = $condition[$i * 2][0];
                        $arr = explode('!', $condition[$i * 2][1]);
                        $boolean = $i > 0 ? $condition[$i * 2 - 1] : 'and';
                        $method = 'where';
                        $val = static::formatValueByOperator($arr[0], $value, $arr[1] ?? null);
                        $args = [$column, $arr[0], $val, $boolean];
                        while ($i * 2 < count($condition)) {
                            $func = 'where' . Str::studly($arr[0]);
                            if (method_exists($builder, $func) || method_exists($builder->getQuery(), $func)) {
                                $method = $func;
                                $args = [$column, $val, $boolean];
                            }
                            $builder->$method(...$args);
                            ++$i;
                        }
                    }
                }
            }
        }

        return $builder;
    }

    protected static function formatValueByOperator($operator, $value, $type)
    {
        if (Str::contains($operator, 'like') !== false) {
            return "%$value%";
        }
        if (Str::contains($operator, 'between') !== false) {
            $value = explode(' - ', $value, 2);
            if (!empty($type)) {
                $value = array_map(function($item) use ($type) {
                    switch ($type) {
                    case 'date':
                    case 'datetime':
                        return date('Y-m-d H:i:s', strtotime($item));
                        break;
                    }
                    return $item;
                }, $value);
            }
            return $value;
        }
        return $value;
    }

    protected static function parseRules($rawRules)
    {
        $rules = [];
        foreach ($rawRules as $filed => $condition) {
            if (is_numeric($filed)) {
                $rules[$condition] = '=';
            } else {
                $operators = ['|' => 'or', ',' => 'and'];
                if (Str::contains($condition, $keys = array_keys($operators))) {
                    $rules[$filed] = array_map(function($item) use ($operators) {
                        if (in_array($item, $operators)) {
                            return $item;
                        }
                        return Str::contains($item, ':') ? explode(':', $item) : [$item, '='];
                    }, explode('#', str_replace($keys, array_map(function($item) {
                        return "#{$item}#";
                    }, $operators), $condition)));
                } else if (Str::contains($condition, ':')) {
                    $rules[$filed] = [explode(':', $condition)];
                } else {
                    $rules[$filed] = $condition;
                }
            }
        }

        return $rules;
    }
}