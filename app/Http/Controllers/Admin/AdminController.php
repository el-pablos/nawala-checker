<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\NawalaChecker\Target;
use App\Models\NawalaChecker\Group;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display admin dashboard.
     */
    public function dashboard(): Response
    {
        $stats = [
            'total_users' => User::count(),
            'total_targets' => Target::count(),
            'total_groups' => Group::count(),
            'active_users' => User::where('is_active', true)->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Display list of all users.
     */
    public function users(): Response
    {
        $users = User::withCount('targets')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show user details.
     */
    public function showUser(User $user): Response
    {
        $user->load(['targets' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Create a new user.
     */
    public function createUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['boolean'],
            'domain_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'is_active' => ['boolean'],
        ]);

        $validated['password'] = bcrypt($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users')
            ->with('success', 'User created successfully.');
    }

    /**
     * Update user.
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'domain_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'is_active' => ['sometimes', 'boolean'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

        return back()->with('success', 'User updated successfully.');
    }

    /**
     * Delete user.
     */
    public function deleteUser(User $user): RedirectResponse
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete yourself.']);
        }

        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Suspend user.
     */
    public function suspendUser(User $user): RedirectResponse
    {
        $user->update(['is_active' => false]);

        return back()->with('success', 'User suspended successfully.');
    }

    /**
     * Reactivate user.
     */
    public function reactivateUser(User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        return back()->with('success', 'User reactivated successfully.');
    }

    /**
     * View system statistics.
     */
    public function statistics(): Response
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_targets' => Target::count(),
            'enabled_targets' => Target::where('enabled', true)->count(),
            'total_groups' => Group::count(),
            'blocked_targets' => Target::whereIn('current_status', ['DNS_FILTERED', 'HTTP_BLOCKPAGE', 'HTTPS_SNI_BLOCK'])->count(),
            'ok_targets' => Target::where('current_status', 'OK')->count(),
        ];

        return Inertia::render('Admin/Statistics', [
            'stats' => $stats,
        ]);
    }

    /**
     * View all targets across all users.
     */
    public function allTargets(): Response
    {
        $targets = Target::with(['owner', 'group'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Admin/Targets', [
            'targets' => $targets,
        ]);
    }
}

