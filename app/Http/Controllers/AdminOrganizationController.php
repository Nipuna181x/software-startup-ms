<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminOrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';

        return Tenancy::withoutScoping(function () use ($search, $status): View {
            $organizations = Organization::query()
                ->withCount('users')
                ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                }))
                ->when($status !== '', fn (Builder $query) => $query->where('is_active', $status === 'active'))
                ->latest()->orderByDesc('id')
                ->paginate(12)->withQueryString();

            return view('pages.admin.organizations', [
                'organizations' => $organizations,
                'search' => $search,
                'status' => $status,
                'stats' => [
                    'Organizations' => Organization::query()->count(),
                    'Active workspaces' => Organization::query()->where('is_active', true)->count(),
                    'Registered users' => User::query()->count(),
                    'New this month' => Organization::query()->where('created_at', '>=', now()->startOfMonth())->count(),
                ],
            ]);
        });
    }

    public function show(Organization $organization): View
    {
        return Tenancy::withoutScoping(function () use ($organization): View {
            return view('pages.admin.organization', [
                'registeredOrganization' => $organization,
                'members' => $organization->users()->orderBy('name')->paginate(15),
            ]);
        });
    }
}
