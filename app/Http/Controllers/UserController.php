<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\SubscriptionGuard;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SubscriptionGuard $subscriptionGuard,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view users', only: ['index', 'show']),
            new Middleware('permission:create users', only: ['create', 'store']),
            new Middleware('permission:edit users', only: ['edit', 'update']),
            new Middleware('permission:delete users', only: ['destroy']),
        ];
    }

    public function index()
    {
        $context = app(TenantContext::class);

        return Inertia::render('Users/Index', [
            'users' => $this->visibleUsersQuery()->with(['roles', 'company', 'branch'])
                ->paginate(10)->through(fn (User $user) => array_merge($user->toArray(), [
                    'can_manage' => $this->canManageUser($user),
                ])),
            'roles' => Role::whereIn('name', $context->assignableRolesFor(auth()->user()))->get(),
            'companies' => $this->availableCompanies(),
            'branches' => $this->availableBranches(),
            'assignable_roles' => $context->assignableRolesFor(auth()->user()),
        ]);
    }

    public function show(User $user)
    {
        abort_unless($this->visibleUsersQuery()->whereKey($user->id)->exists(), 403);

        $user->load(['roles', 'company', 'branch']);

        return Inertia::render('Users/Show', [
            'user' => array_merge($user->toArray(), [
                'can_manage' => $this->canManageUser($user),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $context = app(TenantContext::class);
        $assignableRoles = $context->assignableRolesFor(auth()->user());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in($assignableRoles)],
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $this->validateAssignment($validated);

        if (! empty($validated['company_id']) && ! empty($validated['branch_id'])) {
            $company = Company::query()->with('subscription')->findOrFail($validated['company_id']);
            $this->subscriptionGuard->assertCanAssignUserToBranch($company, (int) $validated['branch_id']);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_id' => $validated['company_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeManagedUser($user);

        $context = app(TenantContext::class);
        $assignableRoles = $context->assignableRolesFor(auth()->user());

        if ($context->isMasterAdmin() && $user->hasRole(UserRole::SuperAdmin->value)) {
            $assignableRoles = [UserRole::SuperAdmin->value];
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => ['required', Rule::in($assignableRoles)],
            'password' => 'nullable|string|min:8',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $this->validateAssignment($validated);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_id' => $validated['company_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeManagedUser($user);
        $user->update([
            'delete_remark' => $request->delete_remark,
            'deleted_by' => Auth::id(),
        ]);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    private function visibleUsersQuery()
    {
        $context = app(TenantContext::class);
        $query = User::query();

        if ($context->isMasterAdmin()) {
            $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', UserRole::SuperAdmin->value));
        } elseif ($context->isSuperAdmin()) {
            $query->whereIn('company_id', $context->ownedCompanyIds())
                ->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery->where('name', UserRole::SuperAdmin->value));
        } elseif (auth()->user()?->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        return $query;
    }

    private function availableCompanies()
    {
        $context = app(TenantContext::class);

        if ($context->isMasterAdmin()) {
            return Company::orderBy('name')->get(['id', 'name']);
        }

        if ($context->isSuperAdmin()) {
            return Company::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name']);
        }

        return Company::where('id', auth()->user()?->company_id)->get(['id', 'name']);
    }

    private function availableBranches()
    {
        $context = app(TenantContext::class);

        if ($context->isMasterAdmin()) {
            return Branch::with('company:id,name')->orderBy('name')->get(['id', 'name', 'company_id']);
        }

        if ($context->isSuperAdmin()) {
            return Branch::with('company:id,name')
                ->whereIn('company_id', $context->ownedCompanyIds())
                ->orderBy('name')
                ->get(['id', 'name', 'company_id']);
        }

        return Branch::with('company:id,name')
            ->where('company_id', auth()->user()?->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);
    }

    private function validateAssignment(array $validated): void
    {
        $context = app(TenantContext::class);
        $role = $validated['role'];

        if ($role === UserRole::SuperAdmin->value) {
            throw ValidationException::withMessages([
                'role' => 'Super Admin accounts are provisioned through the central onboarding API.',
            ]);
        }

        if (in_array($role, UserRole::requiresBranchAssignment(), true)) {
            if (empty($validated['company_id']) || empty($validated['branch_id'])) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Company and branch are required for this role.',
                ]);
            }

            $branch = Branch::withoutGlobalScopes()->find($validated['branch_id']);

            if (! $branch || (int) $branch->company_id !== (int) $validated['company_id']) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Selected branch does not belong to the selected company.',
                ]);
            }
        }

        if ($context->isSuperAdmin()) {
            if (! in_array((int) $validated['company_id'], $context->ownedCompanyIds(), true)) {
                throw ValidationException::withMessages([
                    'company_id' => 'You can only assign users to your registered companies.',
                ]);
            }
        }
    }

    private function authorizeManagedUser(User $user): void
    {
        if (! $this->canManageUser($user)) {
            abort(403, 'Super Admin accounts can only be managed by the Master Admin.');
        }
    }

    private function canManageUser(User $user): bool
    {
        $context = app(TenantContext::class);

        if ($context->isMasterAdmin()) {
            return true;
        }

        if ($context->isSuperAdmin()) {
            if ($user->hasRole(UserRole::SuperAdmin->value)) {
                return false;
            }

            return $user->company_id
                && in_array((int) $user->company_id, $context->ownedCompanyIds(), true);
        }

        return (int) $user->company_id === (int) auth()->user()?->company_id;
    }
}
