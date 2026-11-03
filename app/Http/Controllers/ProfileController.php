<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $devices = config('session.driver') === 'database'
            ? DB::table(config('session.table', 'sessions'))->where('user_id', $request->user()->id)->count()
            : null;

        return view('profile.edit', ['user' => $request->user(), 'devices' => $devices]);
    }

    /** Siswa dan Admin/Toolman sama-sama boleh mengubah profil sendiri. */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $rules = [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        if (! $user->isAdmin()) {
            $rules['kelas'] = ['required', 'string', 'max:30'];
        }

        $user->update($request->validate($rules));

        return back()->with('toast', 'Profil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $request->user()->update(['password' => $data['password']]);

        // Perangkat lain harus login ulang setelah kata sandi diganti.
        Auth::logoutOtherDevices($data['password']);

        return back()->with('toast', 'Kata sandi diganti. Perangkat lain sudah dikeluarkan.');
    }

    public function logoutOthers(Request $request): RedirectResponse
    {
        $request->validateWithBag('devices', [
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($request->input('password'));

        return back()->with('toast', 'Semua perangkat lain sudah dikeluarkan.');
    }
}
