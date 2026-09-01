<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Every signed-in user edits their own account here: their name, how to reach
 * them, the language they work in, and their password. Roles and the active
 * flag stay with the user manager — nobody promotes themselves from this page.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'locale' => ['required', 'in:ar,fr,en'],
        ]);

        $user->update($data);

        // The header language menu writes the same field; keep the session in step.
        $request->session()->put('locale', $data['locale']);

        return redirect()->route('profile.edit')->with('status', __('profile.saved'));
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('profile.edit')->with('status', __('profile.password_saved'));
    }
}
