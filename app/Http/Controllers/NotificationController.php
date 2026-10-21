<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(15),
        ]);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('toast', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /** Tandai dibaca lalu buka laporan terkait sesuai role. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        // Pengajuan akun baru -> halaman persetujuan akun milik Toolman.
        if (($notification->data['account_request'] ?? false) && $request->user()->isAdmin()) {
            return redirect()->route('admin.accounts.index');
        }

        $code = $notification->data['report_code'] ?? null;

        if (! $code) {
            return redirect()->route('notifications.index');
        }

        return redirect()->route(
            $request->user()->isAdmin() ? 'admin.reports.show' : 'student.reports.show',
            $code
        );
    }
}
