<?php

namespace Viviniko\Repository;

interface SearchRequest
{

    /**
     * Search repository.
     *
     * @param CrudRepository $repository
     * @return mixed
     */
    public function apply(CrudRepository $repository);

}