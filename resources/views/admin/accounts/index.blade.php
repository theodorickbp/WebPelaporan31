@extends('layouts.app')

@section('title', 'Akun')

@section('content')
    <div class="page-head">
        <h1>Pengajuan Akun</h1>
        <p class="muted">Periksa data pendaftar (cocokkan NISN/NIP dan nama), lalu setujui atau tolak.</p>
    </div>

    @php
        $tabs = [
            ['key' => 'pending', 'label' => 'Menunggu'],
            ['key' => 'approved', 'label' => 'Disetujui'],
            ['key' => 'rejected', 'label' => 'Ditolak'],
            ['key' => 'all', 'label' => 'Semua'],
        ];
        $total = array_sum($counts);
        $emptyText = [
            'pending' => 'Tidak ada pengajuan yang menunggu. Semua sudah diputuskan.',
            'approved' => 'Belum ada akun yang disetujui.',
            'rejected' => 'Belum ada pengajuan yang ditolak.',
        ][$status] ?? 'Tidak ada akun yang cocok.';
    @endphp

    <nav class="tabs" aria-label="Filter status akun">
        @foreach ($tabs as $tab)
            @php $n = $tab['key'] === 'all' ? $total : ($counts[$tab['key']] ?? 0); @endphp
            <a href="{{ route('admin.accounts.index', array_filter(['status' => $tab['key'], 'q' => $q])) }}"
               @class(['tab-pill', 'is-active' => $status === $tab['key']]) @if ($status === $tab['key']) aria-current="page" @endif>
                {{ $tab['label'] }} <span class="count">{{ $n }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.accounts.index') }}" class="account-search section">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="field">
            <label for="q">Cari</label>
            <input id="q" name="q" type="search" class="input" value="{{ $q }}" placeholder="Nama, email, atau NISN/NIP">
        </div>
        <button type="submit" class="btn btn-soft"><x-icon name="search" :size="18" /> Cari</button>
    </form>

    <div class="section">
        @forelse ($accounts as $account)
            @if ($loop->first) <div class="account-list"> @endif
            @php
                $isTeacher = $account->role === \App\Enums\UserRole::Teacher;
                $state = $account->account_status;
            @endphp
            <article class="panel account">
                <div class="row row-between">
                    <div class="account-name">
                        <strong>{{ $account->name }}</strong>
                        <span class="chip chip-wait">{{ $account->role->label() }}</span>
                    </div>
                    <span class="chip {{ $state->chip() }}">{{ $state->label() }}</span>
                </div>

                <dl class="details">
                    <div><dt>{{ $isTeacher ? 'NIP / NUPTK' : 'NISN' }}</dt><dd>{{ $account->nomor_induk ?: '-' }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $account->email }}</dd></div>
                    <div>
                        <dt>No. Telpon</dt>
                        <dd>@if ($account->phone)<a href="tel:{{ $account->phone }}">{{ $account->phone }}</a>@else - @endif</dd>
                    </div>
                    <div><dt>Diajukan</dt><dd>{{ $account->created_at->translatedFormat('j F Y, H:i') }}</dd></div>
                    @if ($account->registration_reason)
                        <div><dt>Alasan / perihal pengajuan</dt><dd>{!! nl2br(e($account->registration_reason)) !!}</dd></div>
                    @endif
                    @if ($state === \App\Enums\AccountStatus::Rejected && $account->rejection_reason)
                        <div><dt>Alasan penolakan</dt><dd>{{ $account->rejection_reason }}</dd></div>
                    @endif
                    @if ($account->reviewed_at)
                        <div><dt>Diputuskan</dt><dd>{{ $account->reviewed_at->translatedFormat('j F Y, H:i') }}</dd></div>
                    @endif
                </dl>

                @if ($state === \App\Enums\AccountStatus::Pending)
                    <div class="account-actions">
                        <form method="POST" action="{{ route('admin.accounts.approve', $account) }}" data-once>
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" :size="18" /> Setujui</button>
                        </form>
                        <form method="POST" action="{{ route('admin.accounts.reject', $account) }}" class="reject-form"
                              data-confirm="Tolak pengajuan akun ini?" data-once>
                            @csrf
                            <input type="text" name="reason" class="input" maxlength="300" placeholder="Alasan penolakan (opsional)" aria-label="Alasan penolakan (opsional)">
                            <button type="submit" class="btn btn-danger-soft"><x-icon name="x" :size="18" /> Tolak</button>
                        </form>
                    </div>
                @elseif ($state === \App\Enums\AccountStatus::Rejected)
                    <div class="account-actions">
                        <form method="POST" action="{{ route('admin.accounts.approve', $account) }}" data-once>
                            @csrf
                            <button type="submit" class="btn btn-soft btn-block"><x-icon name="check" :size="18" /> Setujui sekarang</button>
                        </form>
                    </div>
                @endif
            </article>
            @if ($loop->last) </div> @endif
        @empty
            <div class="panel empty"><p>{{ $emptyText }}</p></div>
        @endforelse

        {{ $accounts->links() }}
    </div>
@endsection
