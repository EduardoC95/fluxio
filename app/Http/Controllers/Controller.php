<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller
{
    use AuthorizesRequests;

    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'links' => $paginator->linkCollection()->map(fn (array $link): array => [
                'url' => $link['url'],
                'label' => strip_tags($link['label']),
                'active' => $link['active'],
            ])->all(),
        ];
    }
}
