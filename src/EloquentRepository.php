<?php

namespace Viviniko\Repository;

use Illuminate\Database\Eloquent\Model;

class EloquentRepository extends AbstractCrudRepository
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * SimpleRepository constructor.
     * @param null $model
     */
    public function __construct($model = null)
    {
        if ($model) {
            $this->model = $model;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save($attributes, $data = null)
    {
        if (method_exists($this, 'beforeSave')) {
            if (($data = $this->beforeSave($attributes, $data)) === false) {
                return false;
            }
        }

        if (is_null($data)) {
            $entity = $this->createQuery()->create($attributes);
        } else {
            $entity = (is_string($attributes) || is_numeric($attributes)) ?
                $this->find($attributes) :
                $this->createQuery()->where($attributes)->first();
            if ($entity)
                $entity->update($data);
        }

        if ($entity && method_exists($this, 'postSave')) {
            $this->postSave($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        if (method_exists($this, 'beforeCreate')) {
            if (($data = $this->beforeCreate($data)) === false) {
                return false;
            }
        }

        $entity = $this->createQuery()->create($data);

        if (method_exists($this, 'postCreate')) {
            $this->postCreate($entity);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
        if (method_exists($this, 'beforeUpdate')) {
            if (($data = $this->beforeUpdate($id, $data)) === false) {
                return false;
            }
        }

        if ($entity = $this->find($id)) {
            $entity->update($data);

            if (method_exists($this, 'postUpdate')) {
                $this->postUpdate($entity);
            }
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if (method_exists($this, 'beforeDelete')) {
            if ($this->beforeDelete($id) === false) {
                return false;
            }
        }

        $deleted = 0;
        $this->findAllBy($this->getKeyName(), $id)->each(function ($entity) use (&$deleted) {
            if ($entity->delete()) {
                if (method_exists($this, 'postDelete')) {
                    $this->postDelete($entity);
                }
                ++ $deleted;
            }
        });

        return $deleted;
    }

    /**
     * Create a new instance of the query builder.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function createQuery()
    {
        return $this->createModel()->newQuery();
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        if (!$this->model instanceof Model) {
            if (is_string($this->model)) {
                $class = '\\'.ltrim($this->model, '\\');
                $this->model = new $class;
            }
        }

        return clone $this->model;
    }
}