@props(['href' => null])
<a href="{{ $href ?? route('home') }}" class="brand" aria-label="SI-PASAR, ke beranda">
    <svg class="brand-mark" viewBox="0 0 32 32" width="32" height="32" aria-hidden="true">
        <rect width="32" height="32" rx="9" fill="#2F5BEA"/>
        <path d="M9 17.5l5 5 9-11" stroke="#FFC933" stroke-width="3.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span class="brand-name">SI-PASAR</span>
</a>
