<?php

namespace Viviniko\Repository;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

abstract class AbstractCrudRepository implements CrudRepository
{
    /**
     * {@inheritdoc}
     */
    public function paginate($pageSize, $search = null, $where = null, $order = null)
    {
        $searchRules = [];
        $query = [];
        $searchName = null;
        if (is_string($search)) {
            $searchName = $search;
            $search = $where;
            $where = [];
        }

        if (is_array($search)) {
            if (Arr::isAssoc($search)) {
                $query = $search;
            } else {
                list($query, $searchRules, $searchName) = $search;
            }
        }

        $where = array_merge($query, $where instanceof Arrayable ? $where->toArray() : (array)$where);
        $builder = $this->search($where, $searchRules);
        if (!empty($order)) {
            $orders = [];
            if (is_string($order)) {
                $orders = [[$order, 'desc']];
            } else if (Arr::isAssoc($order)) {
                foreach ($order as $name => $direct) {
                    $orders[] = [$name, $direct];
                }
            } else {
                $orders = $order;
            }
            foreach ($orders as $params) {
                $builder->orderBy(...(is_array($params) ? $params : [$params, 'desc']));
            }
        }
        $items = $builder->paginate($pageSize);
        if (!empty($query) && is_string($searchName)) {
            $items->appends([$searchName => $query]);
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function all($columns = ['*'])
    {
        return $this->createQuery()->get($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $columns = ['*'])
    {
        return $this->createQuery()->find($id, (array) $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        return $this->createQuery()->insert($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
        return $this->createQuery()->where((is_numeric($id) || is_string($id)) ? ['id' => $id] : $id)->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->createQuery()->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function pluck($column, $key = null)
    {
        return $this->createQuery()->pluck($column, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllBy($column, $value = null, $columns = ['*'])
    {
        return $this->where($column, $value)->get((array) $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy($column, $value = null, $columns = ['*'])
    {
        return $this->where($column, $value)->first((array) $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($column, $value = null)
    {
        return $this->where($column, $value)->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function count($column = null, $value = null)
    {
        $query = $column ? $this->where($column, $value) : $this->createQuery();

        return $query->count();
    }

    /**
     * Search.
     *
     * @param mixed $keywords
     * @param null $rules
     *
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function search($keywords, $rules = null)
    {
        return BuilderFactory::make($this->createQuery(), $keywords, $rules);
    }

    /**
     * @param $column
     * @param null $value
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function where($column, $value = null)
    {
        $query = $this->createQuery();

        if (!is_array($column) && !$column instanceof Arrayable) {
            $column = [$column => $value];
            $value = null;
        }

        $boolean = $value ?: 'and';
        foreach ($column as $field => $value) {
            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($field, $value, $boolean);
            } else {
                $query->where($field, '=', $value, $boolean);
            }
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    abstract public function createQuery();
}