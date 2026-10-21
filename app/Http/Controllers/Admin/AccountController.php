<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/** Toolman menyetujui atau menolak pengajuan akun Siswa/Guru. */
class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', AccountStatus::Pending->value);
        $q = trim((string) $request->query('q', ''));

        $base = User::query()->where('role', '!=', UserRole::Admin->value);

        $counts = (clone $base)
            ->selectRaw('account_status, count(*) as total')
            ->groupBy('account_status')
            ->pluck('total', 'account_status')
            ->all();

        $query = clone $base;

        if (AccountStatus::tryFrom((string) $status)) {
            $query->where('account_status', $status);
        } else {
            $status = 'all';
        }

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('nomor_induk', 'like', $like);
            });
        }

        // Antrean pengajuan: yang paling lama menunggu di atas. Lainnya: yang terbaru di atas.
        $status === AccountStatus::Pending->value ? $query->oldest() : $query->latest('updated_at');

        return view('admin.accounts.index', [
            'accounts' => $query->paginate(12)->withQueryString(),
            'counts' => $counts,
            'status' => $status,
            'q' => $q,
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        if ($user->account_status === AccountStatus::Approved) {
            return back()->with('toast', 'Akun ini sudah disetujui.');
        }

        $user->forceFill([
            'account_status' => AccountStatus::Approved,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ])->save();

        $mailed = $this->tellApplicant($user, new AccountReviewed(true));

        return back()->with('toast', 'Akun '.$user->name.' disetujui.'.($mailed ? '' : ' (Email pemberitahuan gagal dikirim.)'));
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        if ($user->account_status !== AccountStatus::Pending) {
            return back()->with('toast', 'Hanya pengajuan yang masih menunggu yang bisa ditolak.');
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:300'],
        ]);

        $reason = filled($data['reason'] ?? null) ? trim($data['reason']) : null;

        $user->forceFill([
            'account_status' => AccountStatus::Rejected,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        $mailed = $this->tellApplicant($user, new AccountReviewed(false, $reason));

        return back()->with('toast', 'Pengajuan '.$user->name.' ditolak.'.($mailed ? '' : ' (Email pemberitahuan gagal dikirim.)'));
    }

    /** Kabari pemilik akun lewat email. Kegagalan kirim tidak boleh membatalkan keputusan Toolman. */
    private function tellApplicant(User $user, AccountReviewed $notification): bool
    {
        try {
            $user->notify($notification);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
