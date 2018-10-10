<?php

namespace Viviniko\Repository;

class SearchPageRequest extends SearchRequest
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

    public function apply(AbstractCrudRepository $repository)
    {
        $result = parent::builder($repository)
            ->paginate($this->size, $this->columns, $this->pageName, $this->page);

        if (!empty($this->params) && !empty($this->requestParam)) {
            $result->appends([$this->requestParam => $this->params]);
        }

        return $result;
    }


}