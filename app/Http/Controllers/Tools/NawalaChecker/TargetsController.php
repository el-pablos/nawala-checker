<?php

namespace App\Http\Controllers\Tools\NawalaChecker;

use App\Http\Controllers\Tools\BaseToolController;
use App\Http\Requests\NawalaChecker\StoreTargetRequest;
use App\Http\Requests\NawalaChecker\UpdateTargetRequest;
use App\Http\Resources\NawalaChecker\TargetResource;
use App\Models\NawalaChecker\Target;
use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Tag;
use App\Services\NawalaChecker\NawalaCheckerService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TargetsController extends BaseToolController
{
    public function __construct(
        protected NawalaCheckerService $service
    ) {}

    /**
     * Display a listing of targets.
     */
    public function index(): Response
    {
        $filters = [
            'search' => $this->getSearchQuery(),
            'owner_id' => request()->input('owner_id'),
            'group_id' => request()->input('group_id'),
            'status' => request()->input('status'),
            'enabled' => request()->input('enabled'),
            'sort' => $this->getSortField('created_at'),
            'direction' => $this->getSortDirection('desc'),
        ];

        $targets = $this->service->getTargets($filters, $this->getPerPage());

        return Inertia::render('tools/nawala-checker/targets/index', [
            'targets' => TargetResource::collection($targets),
            'filters' => $filters,
            'groups' => Group::select('id', 'name', 'slug')->get(),
            'tags' => Tag::select('id', 'name', 'slug', 'color')->get(),
            'stats' => $this->service->getDashboardStats(auth()->id()),
        ]);
    }

    /**
     * Show the form for creating a new target.
     */
    public function create(): Response
    {
        return Inertia::render('tools/nawala-checker/targets/create', [
            'groups' => Group::select('id', 'name', 'slug')->get(),
            'tags' => Tag::select('id', 'name', 'slug', 'color')->get(),
        ]);
    }

    /**
     * Store a newly created target.
     */
    public function store(StoreTargetRequest $request): RedirectResponse
    {
        $target = $this->service->createTarget($request->validated());

        return redirect()
            ->route('nawala-checker.targets.index')
            ->with('success', 'Target berhasil ditambahkan.');
    }

    /**
     * Display the specified target.
     */
    public function show(Target $target): Response
    {
        $target->load(['owner', 'group', 'tags', 'latestCheckResult']);

        return Inertia::render('tools/nawala-checker/targets/show', [
            'target' => new TargetResource($target),
            'checkResults' => $this->service->getCheckResults($target, 24),
            'statistics' => $this->service->getTargetStatistics($target, 7),
        ]);
    }

    /**
     * Show the form for editing the target.
     */
    public function edit(Target $target): Response
    {
        $target->load(['group', 'tags']);

        return Inertia::render('tools/nawala-checker/targets/edit', [
            'target' => new TargetResource($target),
            'groups' => Group::select('id', 'name', 'slug')->get(),
            'tags' => Tag::select('id', 'name', 'slug', 'color')->get(),
        ]);
    }

    /**
     * Update the specified target.
     */
    public function update(UpdateTargetRequest $request, Target $target): RedirectResponse
    {
        $this->service->updateTarget($target, $request->validated());

        return redirect()
            ->route('nawala-checker.targets.index')
            ->with('success', 'Target berhasil diperbarui.');
    }

    /**
     * Remove the specified target.
     */
    public function destroy(Target $target): RedirectResponse
    {
        $this->service->deleteTarget($target);

        return redirect()
            ->route('nawala-checker.targets.index')
            ->with('success', 'Target berhasil dihapus.');
    }

    /**
     * Run check now for the target.
     */
    public function runCheck(Target $target)
    {
        $result = $this->service->runCheckNow($target);

        return back()->with('success', 'Check berhasil dijalankan. Status: ' . $result['status']);
    }

    /**
     * Toggle target enabled status.
     */
    public function toggle(Target $target): RedirectResponse
    {
        $target->update(['enabled' => !$target->enabled]);

        $status = $target->enabled ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Target berhasil {$status}.");
    }
}

