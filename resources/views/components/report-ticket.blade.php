@props(['report', 'href', 'showUser' => false])
@php $cover = $report->photos->first(); @endphp
<a href="{{ $href }}" class="ticket">
    <div class="ticket-main">
        <div class="ticket-thumb">
            @if ($cover)
                <img src="{{ route('photos.thumb', $cover) }}" alt="" loading="lazy" width="64" height="64">
            @else
                <x-icon :name="$report->category->icon()" :size="26" />
            @endif
        </div>
        <div class="ticket-body">
            <div class="ticket-title">{{ $report->title }}</div>
            <div class="ticket-meta">
                <span class="ticket-meta-item"><x-icon name="map-pin" :size="14" /> {{ $report->location }}</span>
                <span class="ticket-meta-item"><x-icon :name="$report->category->icon()" :size="14" /> {{ $report->category->label() }}</span>
            </div>
            @if ($showUser)
                <div class="ticket-user">oleh {{ $report->user->name }}, {{ $report->user->reporterLabel() }}</div>
            @endif
            @if ($report->isOverdue())
                <div class="ticket-overdue"><x-icon name="clock" :size="14" /> Menunggu lama, segera ditinjau</div>
            @endif
        </div>
        <x-status-chip :status="$report->status" />
    </div>
    <div class="ticket-stub">
        <span class="ticket-code">{{ $report->code }}</span>
        <time datetime="{{ $report->created_at->toIso8601String() }}">{{ $report->created_at->diffForHumans() }}</time>
    </div>
</a>
