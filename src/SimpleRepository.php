<?php

namespace Viviniko\Repository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SimpleRepository extends AbstractCrudRepository
{
    /**
     * @var string
     */
    protected $table;

    /**
     * SimpleRepository constructor.
     * @param null $table
     */
    public function __construct($table = null)
    {
        if ($table) {
            $this->table = $table;
        }
    }

    public static function createFromModel($model)
    {
        if (is_string($model) && class_exists($model)) {
            $model = new $model;
        }
        throw_if(!$model instanceof Model, new \InvalidArgumentException());
        
        return new static($model->getTable());
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function createQuery()
    {
        return DB::table($this->getTable());
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
}