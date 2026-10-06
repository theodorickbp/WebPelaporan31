@props(['status'])
<span class="chip chip-{{ $status->tone() }}">{{ $status->label() }}</span>
