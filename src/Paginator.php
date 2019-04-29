<?php

namespace Viviniko\Repository;

use Illuminate\Contracts\Support\Arrayable;

trait Paginator
{
    private $defaultSearchOptions = [
        'page_size' => 25,
        'request_param_name' => 'search',
        'rules' => [],
    ];

    protected $searchOptions = [];

    /**
     * Paginate repository.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $wheres
     * @param array $orders
     * @param null $options
     * @return mixed
     */
    public function paginateByRequest(Request $request, $wheres = [], $orders = [], $options = null)
    {
        $options = $options instanceof Arrayable ? $options->toArray() : ($options ?? []);
        $options = array_merge($this->defaultSearchOptions, $this->searchOptions, $options);

        return $this->search(
            SearchPageRequest::create($options['page_size'], $wheres, $orders)
                ->rules($options['rules'])
                ->request($request, $options['request_param_name'])
        );
    }
}