<?php

namespace Viviniko\Repository;

use Illuminate\Contracts\Support\Arrayable;

abstract class AbstractCrudRepository implements CrudRepository
{
    /**
     * {@inheritdoc}
     */
    public function search(SearchRequest $searchRequest)
    {
        return $searchRequest->apply($this);
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
    public function save($attributes, $data = null)
    {
        if (is_null($data)) {
            $this->createQuery()->insert($attributes);
        } else {
            $this->createQuery()
                ->where((is_string($attributes) || is_numeric($attributes)) ? ['id' => $attributes] : $attributes)
                ->update($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        $id = $this->createQuery()->insertGetId($data);

        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
        $this->createQuery()->where((is_numeric($id) || is_string($id)) ? ['id' => $id] : $id)->update($data);

        return $this->find($id);
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
        if (is_array($column) || $column instanceof Arrayable) {
            $columns = $value ?: $columns;
        }

        return $this->where($column, $value)->get((array) $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy($column, $value = null, $columns = ['*'])
    {
        if (is_array($column) || $column instanceof Arrayable) {
            $columns = $value ?: $columns;
        }

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