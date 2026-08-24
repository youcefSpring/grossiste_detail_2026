<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'users' => User::with('roles')->orderBy('name')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('users.form', [
            'user' => new User(['is_active' => true, 'locale' => 'ar']),
            'roles' => array_keys(Permissions::ROLES),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = User::create([
            ...collect($data)->except('role')->all(),
            'password' => $data['password'],
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('users.index')
            ->with('status', __('user.created', ['name' => $user->name]));
    }

    public function edit(User $user)
    {
        return view('users.form', [
            'user' => $user,
            'roles' => array_keys(Permissions::ROLES),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        // Check before writing anything: demoting the only owner would lock everyone out.
        if ($user->hasRole('owner') && $data['role'] !== 'owner' && $this->ownerCount() <= 1) {
            return back()->withInput()->withErrors(['role' => __('user.last_owner')]);
        }

        $attributes = collect($data)->except(['role', 'password'])->all();

        if (! empty($data['password'])) {
            $attributes['password'] = $data['password'];
        }

        $user->update($attributes);
        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('users.index')
            ->with('status', __('user.updated', ['name' => $user->name]));
    }

    /** Deactivate rather than delete — their sales and movements stay attributable. */
    public function toggle(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => __('user.not_yourself')]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', __($user->is_active ? 'user.enabled' : 'user.disabled', ['name' => $user->name]));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'locale' => ['required', 'in:ar,fr,en'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(array_keys(Permissions::ROLES))],
            'is_active' => ['boolean'],
        ]);
    }

    private function ownerCount(): int
    {
        return User::role('owner')->where('is_active', true)->count();
    }
}
