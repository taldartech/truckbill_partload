<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller implements HasMiddleware
{
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
        $query = User::with(['roles', 'company']);
        
        if (auth()->check() && auth()->user()->company_id) {
            if (!auth()->user()->hasRole(['master_admin', 'super_admin'])) {
                $query->where('company_id', auth()->user()->company_id);
            }
        }

        return Inertia::render('Users/Index', [
            'users' => $query->paginate(10),
            'roles' => Role::all(),
            'companies' => Company::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
            'company_id' => 'nullable|exists:companies,id'
        ]);

        $company_id = $validated['company_id'] ?? null;
        if (auth()->check() && !auth()->user()->hasRole(['master_admin', 'super_admin'])) {
            $company_id = auth()->user()->company_id;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_id' => $company_id,
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|exists:roles,name',
            'password' => 'nullable|string|min:8',
            'company_id' => 'nullable|exists:companies,id'
        ]);

        $company_id = $validated['company_id'] ?? null;
        if (auth()->check() && !auth()->user()->hasRole(['master_admin', 'super_admin'])) {
            $company_id = auth()->user()->company_id;
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_id' => $company_id,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete yourself.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
