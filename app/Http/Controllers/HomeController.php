<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function landing(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return view('landing');
    }

    /** Titik masuk setelah login: arahkan sesuai role. */
    public function dashboard(Request $request): RedirectResponse
    {
        $user = $request->user();

        return redirect()->route(match (true) {
            $user->isAdmin() => 'admin.home',
            $user->isToolman() => 'toolman.home',
            default => 'student.home',
        });
    }
}
