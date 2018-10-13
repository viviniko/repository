<?php

namespace Viviniko\Repository;

use Illuminate\Support\Arr;

class SearchDataRequest implements SearchRequest
{
    /**
     * @var int
     */
    protected $size;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var array
     */
    protected $rules;

    /**
     * @var array
     */
    protected $wheres;

    /**
     * @var array
     */
    protected $orders;

    /**
     * @var array
     */
    protected $columns = ['*'];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $requestParam;

    public function __construct($size = 1000, array $wheres = [], array $orders = [])
    {
        $this->size = $size;
        $this->wheres = $wheres;
        if (Arr::isAssoc($orders)) {
            foreach ($orders as $name => $direct) {
                $this->orders[] = [$name, $direct];
            }
        } else {
            $this->orders = $orders;
        }
    }

    public static function create($size = 1000, array $wheres = [], array $orders = [])
    {
        return new static($size, $wheres, $orders);
    }

    public function rules(array $rules)
    {
        $this->rules = array_merge($this->rules ?? [], $rules);

        return $this;
    }

    public function params(array $params)
    {
        $this->params = array_merge($this->params ?? [], $params);

        return $this;
    }

    public function columns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function request($request, $requestParam = null)
    {
        $this->request = $request;
        $this->requestParam = $requestParam;
        $params = $this->requestParam ? $this->request->get($this->requestParam) : $this->request->all();
        if (!is_array($params) && $this->requestParam) {
            $params = [$this->requestParam => $params];
        }

        if (is_array($params)) {
            $this->params($params);
        }

        return $this;
    }

    public function filter($filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    public function where($field, $value)
    {
        $this->wheres[$field] = $value;

        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [$column, strtolower($direction) == 'asc' ? 'asc' : 'desc'];

        return $this;
    }

    public function take($size)
    {
        $this->size = $size;

        return $this;
    }

    public function apply(CrudRepository $repository)
    {
        return $this->builder($repository)
            ->limit($this->size)
            ->get($this->columns);
    }

    protected function builder(CrudRepository $repository)
    {
        if (!$repository instanceof AbstractCrudRepository) {
            throw new \InvalidArgumentException();
        }

        $builder = BuilderFactory::make($repository->where($this->wheres), $this->params, $this->rules);
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                if (is_callable($filter)) {
                    $builder = $filter($builder);
                }
            }
        }
        if (is_array($this->orders)) {
            foreach ($this->orders as $orders) {
                $builder->orderBy(...(is_array($orders) ? $orders : [$orders, 'desc']));
            }
        }

        return $builder;
    }
}