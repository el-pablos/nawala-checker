<?php

namespace App\Http\Controllers\Tools\NawalaChecker;

use App\Http\Controllers\Tools\BaseToolController;
use App\Services\NawalaChecker\NawalaCheckerService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends BaseToolController
{
    public function __construct(
        protected NawalaCheckerService $service
    ) {}

    /**
     * Display the dashboard.
     */
    public function index(): Response
    {
        $stats = $this->service->getDashboardStats(auth()->id());

        return Inertia::render('tools/nawala-checker/dashboard', [
            'stats' => $stats,
        ]);
    }
}

