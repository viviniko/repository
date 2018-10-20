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
            if (class_exists($table)) {
                $table = new $table;
            }
            if ($table instanceof Model) {
                $table = $table->getTable();
            }
            $this->table = $table;
        }
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