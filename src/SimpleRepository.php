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
        if (method_exists($this, 'validateCreateData')) {
            $this->validateCreateData($data);
        }

        return $this->createModel()->newQuery()->create($data);
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
        if (method_exists($this, 'validateUpdateData')) {
            $this->validateUpdateData($id, $data);
        }

        $entity = $this->find($id);

        if ($entity) {
            $entity->update($data);
        }

        return $entity;
    }

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function delete($id)
    {
        $entity = $this->find($id);

        return $entity ? $entity->delete() : null;
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