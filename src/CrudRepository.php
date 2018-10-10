<?php

namespace Viviniko\Repository;

interface CrudRepository
{
    /**
     * Search.
     *
     * @param SearchRequest $searchRequest
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function search(SearchRequest $searchRequest);

    /**
     * All data.
     *
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function all($columns = ['*']);

    /**
     * Find data by id
     *
     * @param  mixed  $id
     * @param  array  $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*']);

    /**
     * Save a new entity in repository
     *
     * @param $attributes
     * @param array $data
     */
    public function save($attributes, $data = null);

    /**
     * Save a new entity in repository
     *
     * @param array $data
     *
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update a entity in repository by id
     *
     * @param       $id
     * @param array $data
     *
     * @return mixed
     */
    public function update($id, array $data);

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return bool
     */
    public function delete($id);

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null);

    /**
     * Find data by field and value
     *
     * @param $column
     * @param null $value
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function findAllBy($column, $value = null, $columns = ['*']);

    /**
     * @param $column
     * @param null $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($column, $value = null, $columns = ['*']);

    /**
     * @param $column
     * @param null $value
     * @return bool
     */
    public function exists($column, $value = null);

    /**
     *
     * @param null $column
     * @param null $value
     * @return int
     */
    public function count($column = null, $value = null);
}