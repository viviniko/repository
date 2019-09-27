<?php

namespace Viviniko\Repository;

class SearchPageRequest extends SearchDataRequest
{
    /**
     * @var int
     */
    protected $page = null;

    /**
     * @var array
     */
    protected $pageName = 'page';

    public function page($page)
    {
        $this->page = $page;

        return $this;
    }

    public function pageName($pageName)
    {
        $this->pageName = $pageName;

        return $this;
    }

    public function apply(CrudRepository $repository)
    {
        $result = parent::builder($repository)
            ->paginate($this->size, $this->columns, $this->pageName, $this->page);

        if (!empty($this->query)) {
            if (!empty($this->queryName)) {
                $result->appends([$this->queryName => $this->query]);
            }
        }

        if (!empty($this->sort)) {
            if (!empty($this->sortName)) {
                $result->appends([$this->sortName => $this->sort]);
            }
        }

        return $result;
    }


}