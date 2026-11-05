<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian di rute: EnsureRole::class.':admin', ':toolman', atau ':student'. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user && ! $user->isStaff() && $user->account_status !== AccountStatus::Approved) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akunmu belum disetujui Admin.',
            ]);
        }

        if (! $user || $user->role->value !== $role) {
            // Sudah login tapi salah area: arahkan ke beranda sesuai rolenya.
            return $user
                ? redirect()->route('dashboard')
                : redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
