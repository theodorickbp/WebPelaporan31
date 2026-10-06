<ol class="timeline">
    @foreach ($report->logs->reverse() as $log)
        <li class="timeline-item tone-{{ $log->to_status->tone() }}">
            <span class="timeline-dot"><x-icon :name="$log->to_status->icon()" :size="14" /></span>
            <div class="timeline-body">
                <strong>{{ $log->to_status->timelineLabel() }}</strong>
                @if ($log->note)
                    <p class="timeline-note">{{ $log->note }}</p>
                @endif
                <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->translatedFormat('j M Y, H:i') }}</time>
            </div>
        </li>
    @endforeach
</ol>
