<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemakaian di rute: EnsureRole::class.':admin' atau EnsureRole::class.':student,teacher'
 * (beberapa role dipisah koma; salah satu cocok sudah cukup).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role->value, $roles, true)) {
            // Sudah login tapi salah area: arahkan ke beranda sesuai rolenya.
            return $user
                ? redirect()->route('dashboard')
                : redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
