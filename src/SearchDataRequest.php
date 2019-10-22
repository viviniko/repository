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
    protected $queryName = 'search';

    /**
     * @var array
     */
    protected $query;

    /**
     * @var string
     */
    protected $sortName = 'sort';

    /**
     * @var array
     */
    protected $sort;

    public function __construct($size = 1000, array $wheres = [], array $orders = [])
    {
        $this->size = $size;
        $this->wheres($wheres);
        $this->orders($orders);
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

    public function query(array $query)
    {
        $this->query = array_merge($this->query ?? [], $query);

        return $this;
    }

    public function sort(array $sort)
    {
        $this->sort = array_merge($this->sort ?? [], $sort);

        return $this;
    }

    public function columns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function request($request, $queryName = null, $sortName = null)
    {
        $this->request = $request;
        $this->queryName = $queryName ?? $this->queryName;
        $this->sortName = $sortName ?? $this->sortName;
        if ($request->has($this->queryName)) {
            $query = $this->request->get($this->queryName);
            if (!is_array($query)) {
                $query = [$this->queryName => $query];
            }
            $this->query($query);
        }
        if ($request->has($this->sortName)) {
            $sort = $this->request->get($this->sortName);
            if (!is_array($sort)) {
                $sort = [$this->sortName => $sort];
            }
            $this->sort($sort);
        }

        return $this;
    }

    public function filter($filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    public function wheres(array $wheres)
    {
        $this->wheres = $wheres;

        return $this;
    }

    public function orders(array $orders)
    {
        if (Arr::isAssoc($orders)) {
            foreach ($orders as $name => $direct) {
                $this->orders[] = [$name, $direct];
            }
        } else {
            $this->orders = $orders;
        }

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

        $builder = BuilderFactory::make($repository->where($this->wheres), $this->query ?? [], $this->rules ?? []);
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                if (is_callable($filter)) {
                    $builder = $filter($builder);
                }
            }
        }
        $orders = array_merge($this->orders ?? [], $this->sort ?? []);
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $builder->orderBy(...(is_array($order) ? $order : [$order, 'desc']));
            }
        }

        return $builder;
    }
}