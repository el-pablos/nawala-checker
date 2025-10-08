<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\BaseController;

abstract class BaseToolController extends BaseController
{
    /**
     * The number of items to paginate.
     */
    protected int $perPage = 15;

    /**
     * Get the pagination count.
     */
    protected function getPerPage(): int
    {
        return request()->input('per_page', $this->perPage);
    }

    /**
     * Get search query from request.
     */
    protected function getSearchQuery(): ?string
    {
        return request()->input('search');
    }

    /**
     * Get sort field from request.
     */
    protected function getSortField(string $default = 'created_at'): string
    {
        return request()->input('sort', $default);
    }

    /**
     * Get sort direction from request.
     */
    protected function getSortDirection(string $default = 'desc'): string
    {
        $direction = request()->input('direction', $default);
        return in_array($direction, ['asc', 'desc']) ? $direction : $default;
    }
}

