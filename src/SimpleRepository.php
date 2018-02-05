<?php

namespace Viviniko\Repository;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

abstract class SimpleRepository
{
    /**
     * @var string
     */
    protected $modelConfigKey;

    /**
     * @var array
     */
    protected $fieldSearchable = [];

    /**
     * Paginate.
     *
     * @param $pageSize
     * @param string $searchName
     * @param null $search
     * @param null $order
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($pageSize, $searchName = 'search', $search = null, $order = null)
    {
        if (!$search) {
            $search = request()->get($searchName);
        }
        $search = is_array($search) ? $search : [];
        $builder = $this->search($search);
        if (!empty($order)) {
            if (is_string($order)) {
                $order = [$order, 'desc'];
            }
            foreach ($order as $column => $direct) {
                $builder->orderBy($column, $direct);
            }
        }
        $items = $builder->paginate($pageSize);
        if (!empty($search)) {
            $items->appends([$searchName => $search]);
        }

        return $items;
    }

    /**
     * Search.
     *
     * @param $keywords
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($keywords)
    {
        return BuilderFactory::make($this->createModel(), $keywords instanceof Request ? $keywords->all() : $keywords, $this->fieldSearchable);
    }

    /**
     * Find data by id
     *
     * @param  mixed  $id
     * @param  array  $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        return $this->createModel()->newQuery()->find($id, (array) $columns);
    }

    /**
     * Save a new entity in repository
     *
     * @param array $data
     *
     * @return mixed
     */
    public function create(array $data)
    {
        if (method_exists($this, 'beforeCreate')) {
            if (($data = $this->beforeCreate($data)) === false) {
                return false;
            }
        }

        $entity = $this->createModel()->newQuery()->create($data);

        if (method_exists($this, 'postCreate')) {
            return $this->postCreate($entity);
        }

        return $entity;
    }

    /**
     * Update a entity in repository by id
     *
     * @param       $id
     * @param array $data
     *
     * @return mixed
     */
    public function update($id, array $data)
    {
        if ($entity = $this->find($id)) {
            if (method_exists($this, 'beforeUpdate')) {
                if (($data = $this->beforeUpdate($id, $data)) === false) {
                    return false;
                }
            }

            $entity->update($data);

            if (method_exists($this, 'postUpdate')) {
                return $this->postUpdate($entity);
            }
        }

        return $entity;
    }

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return bool
     */
    public function delete($id)
    {
        if ($entity = $this->find($id)) {
            if (method_exists($this, 'beforeDelete')) {
                if ($this->beforeDelete($id) === false) {
                    return false;
                }
            }

            if ($result = $entity->delete()) {
                if (method_exists($this, 'postDelete')) {
                    return $this->postDelete($entity);
                }

                return $result;
            }
        }

        return false;
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        return $this->createModel()->newQuery()->pluck($column, $key);
    }

    /**
     * Find data by field and value
     *
     * @param $column
     * @param null $value
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findBy($column, $value = null, $columns = ['*'])
    {
        $query = $this->createModel()->newQuery();
        if (is_array($column)) {
            $boolean = $value ?: 'and';
            foreach ($column as $field => $value) {
                $query->where($field, '=', $value, $boolean);
            }
        } else {
            $query->where($column, $value);
        }

        return $query->get((array) $columns);
    }

    /**
     * @param $column
     * @param null $value
     * @return bool
     */
    public function exists($column, $value = null)
    {
        $query = $this->createModel()->newQuery();
        if (is_array($column)) {
            $boolean = $value ?: 'and';
            foreach ($column as $field => $value) {
                $query->where($field, '=', $value, $boolean);
            }
        } else {
            $query->where($column, $value);
        }

        return $query->exists();
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        static $model;

        if (!$model) {
            $class = '\\'.ltrim(Config::get($this->modelConfigKey), '\\');
            $model = new $class;
        }

        return clone $model;
    }
}