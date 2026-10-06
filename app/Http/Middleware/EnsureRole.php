<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian di rute: EnsureRole::class.':admin' atau EnsureRole::class.':student'. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || $user->role->value !== $role) {
            // Sudah login tapi salah area: arahkan ke beranda sesuai rolenya.
            return $user
                ? redirect()->route('dashboard')
                : redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
