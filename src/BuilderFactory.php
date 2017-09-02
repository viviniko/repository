<?php

namespace Viviniko\Repository;

use Illuminate\Support\Str;

class BuilderFactory
{
    /**
     * Create a new search builder instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string|array  $query
     * @param  array  $rules
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function make($model, $query, $rules)
    {
        $builder = $model->newQuery();
        $rules = static::parseRules($rules);

        if (!empty($query) && is_array($query)) {
            foreach ($query as $name => $value) {
                if ($value != '' && isset($rules[$name])) {
                    $condition = $rules[$name];
                    if (is_string($condition)) {
                        if (Str::contains($condition, 'between')) {
                            $builder->whereBetween($name, static::formatValueByOperator($condition, $value));
                        } else {
                            $builder->where($name, $condition, static::formatValueByOperator($condition, $value));
                        }
                    } else if (is_array($condition)) {
                        $builder->where(function($subQuery) use ($condition, $value) {
                            $i = 0;
                            while ($i * 2 < count($condition)) {
                                $subQuery->where(
                                    $condition[$i * 2][0],
                                    $condition[$i * 2][1],
                                    static::formatValueByOperator($condition[$i * 2][1], $value),
                                    $i > 0 ? $condition[$i * 2 - 1] : 'and'
                                );
                                ++$i;
                            }
                        });
                    }
                }
            }
        }

        return $builder;
    }

    protected static function formatValueByOperator($operator, $value)
    {
        if (Str::contains($operator, 'like') !== false) {
            return "%$value%";
        }
        if (Str::contains($operator, 'between') !== false) {
            $value = explode(' - ', $value, 2);
            if ($type = strtolower(last(explode('between', $operator)))) {
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