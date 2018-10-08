<?php

namespace Viviniko\Repository;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SimpleRepository
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $searchRules = [];

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * SimpleRepository constructor.
     * @param null $model
     */
    public function __construct($model = null)
    {
        if ($model) {
            $this->model = $model;
        }

        $this->init();
    }

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
        $searchRules = [];
        $query = [];
        if (is_array($searchName)) {
            if (Arr::isAssoc($searchName)) {
                $query = $searchName;
            } else {
                list($query, $searchRules) = $searchName;
                if (is_string($query)) {
                    $searchName = $query;
                }
            }
        }

        if (is_string($searchName) && $this->request) {
            $query = (array)$this->request->get($searchName);
        }

        $search = array_merge($query, $search instanceof Arrayable ? $search->toArray() : (array)$search);
        $builder = $this->search($search, $searchRules);
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
     * All data.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all($columns = ['*'])
    {
        return $this->createModel()->newQuery()->get($columns);
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
            $this->postCreate($entity);
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
                $this->postUpdate($entity);
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
                    $this->postDelete($entity);
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findAllBy($column, $value = null, $columns = ['*'])
    {
        return $this->where($column, $value)->get((array) $columns);
    }

    /**
     * @param $column
     * @param null $value
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     */
    public function findBy($column, $value = null, $columns = ['*'])
    {
        return $this->where($column, $value)->first((array) $columns);
    }

    /**
     * @param $column
     * @param null $value
     * @return bool
     */
    public function exists($column, $value = null)
    {
        return $this->where($column, $value)->exists();
    }

    public function count($column = null, $value = null)
    {
        $query = $column ? $this->where($column, $value) : $this->createModel();

        return $query->count();
    }

    /**
     * Search.
     *
     * @param mixed $keywords
     * @param null $rules
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function search($keywords = null, $rules = null)
    {
        $keywords = $keywords ?: $this->request;
        $rules = $rules ? array_merge($this->searchRules, $rules) : $this->searchRules;

        return BuilderFactory::make($this->createModel(), $keywords instanceof Request ? $keywords->all() : $keywords, $rules);
    }

    /**
     * @param $column
     * @param null $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function where($column, $value = null)
    {
        $query = $this->createModel()->newQuery();

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
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function model()
    {
        return $this->createModel();
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
            if ($this->model instanceof Model) {
                $model = $this->model;
            } else if (is_string($this->model)) {
                $class = '\\'.ltrim($this->model, '\\');
                $model = new $class;
            }
        }

        return clone $model;
    }

    public function init()
    {

    }
}