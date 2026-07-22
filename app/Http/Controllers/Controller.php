<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class Controller
{
    /**
     * Paginates an already-fetched Collection — for list pages whose
     * filters include a computed accessor that can't be expressed in SQL
     * (e.g. StudentFee::status), where filtering has to happen in PHP after
     * fetching, so Eloquent's own ->paginate() (which filters and paginates
     * in the same query) can't be used.
     */
    protected function paginateCollection(Collection $items, Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $page = (int) $request->query('page', 1);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
