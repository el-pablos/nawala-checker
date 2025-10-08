<?php

namespace App\Http\Controllers\Tools\NawalaChecker;

use App\Http\Controllers\Tools\BaseToolController;
use App\Http\Requests\NawalaChecker\StoreShortlinkRequest;
use App\Http\Resources\NawalaChecker\ShortlinkResource;
use App\Models\NawalaChecker\Shortlink;
use App\Models\NawalaChecker\ShortlinkGroup;
use App\Models\NawalaChecker\ShortlinkTarget;
use App\Services\NawalaChecker\NawalaCheckerService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ShortlinksController extends BaseToolController
{
    public function __construct(
        protected NawalaCheckerService $service
    ) {}

    /**
     * Display a listing of shortlinks.
     */
    public function index(): Response
    {
        $filters = [
            'search' => $this->getSearchQuery(),
            'group_id' => request()->input('group_id'),
            'is_active' => request()->input('is_active'),
            'sort' => $this->getSortField('created_at'),
            'direction' => $this->getSortDirection('desc'),
        ];

        $shortlinks = $this->service->getShortlinks($filters, $this->getPerPage());

        return Inertia::render('tools/nawala-checker/shortlinks/index', [
            'shortlinks' => ShortlinkResource::collection($shortlinks),
            'filters' => $filters,
            'groups' => ShortlinkGroup::select('id', 'name', 'slug')->get(),
        ]);
    }

    /**
     * Show the form for creating a new shortlink.
     */
    public function create(): Response
    {
        return Inertia::render('tools/nawala-checker/shortlinks/create', [
            'groups' => ShortlinkGroup::select('id', 'name', 'slug')->get(),
        ]);
    }

    /**
     * Store a newly created shortlink.
     */
    public function store(StoreShortlinkRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $targets = $data['targets'];
            unset($data['targets']);

            $shortlink = Shortlink::create($data);

            foreach ($targets as $targetData) {
                $target = ShortlinkTarget::create([
                    'shortlink_id' => $shortlink->id,
                    'url' => $targetData['url'],
                    'priority' => $targetData['priority'],
                    'weight' => $targetData['weight'],
                    'is_active' => $targetData['is_active'] ?? true,
                ]);

                // Set first target as current and original
                if (!$shortlink->current_target_id) {
                    $shortlink->update([
                        'current_target_id' => $target->id,
                        'original_target_id' => $target->id,
                    ]);
                }
            }
        });

        return redirect()
            ->route('nawala-checker.shortlinks.index')
            ->with('success', 'Shortlink berhasil ditambahkan.');
    }

    /**
     * Display the specified shortlink.
     */
    public function show(Shortlink $shortlink): Response
    {
        $shortlink->load(['group', 'currentTarget', 'targets', 'rotationHistory']);

        return Inertia::render('tools/nawala-checker/shortlinks/show', [
            'shortlink' => new ShortlinkResource($shortlink),
        ]);
    }

    /**
     * Remove the specified shortlink.
     */
    public function destroy(Shortlink $shortlink): RedirectResponse
    {
        $shortlink->delete();

        return redirect()
            ->route('nawala-checker.shortlinks.index')
            ->with('success', 'Shortlink berhasil dihapus.');
    }

    /**
     * Rotate shortlink to next target.
     */
    public function rotate(Shortlink $shortlink): RedirectResponse
    {
        $success = $this->service->rotateShortlink($shortlink, auth()->id(), 'manual');

        if ($success) {
            return back()->with('success', 'Shortlink berhasil dirotasi.');
        }

        return back()->with('error', 'Gagal merotasi shortlink. Tidak ada target alternatif yang tersedia.');
    }

    /**
     * Rollback shortlink to original target.
     */
    public function rollback(Shortlink $shortlink): RedirectResponse
    {
        $success = $this->service->rollbackShortlink($shortlink, auth()->id());

        if ($success) {
            return back()->with('success', 'Shortlink berhasil di-rollback ke target original.');
        }

        return back()->with('error', 'Gagal rollback shortlink.');
    }
}

