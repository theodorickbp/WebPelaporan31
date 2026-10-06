{{--
    Viewer + skrip zoom foto. Sengaja TIDAK dimuat di layouts/app.blade.php secara global -
    hanya halaman yang benar-benar punya foto (galeri laporan / draft unggahan) yang
    meng-include ini, supaya halaman lain (Beranda, Profil, Notifikasi, dst) tidak
    menanggung beban JS yang tidak mereka pakai.
--}}
@push('zoom-viewer')
    @include('partials.viewer')
    <script src="{{ asset('js/zoom-viewer.min.js') }}?v={{ @filemtime(public_path('js/zoom-viewer.min.js')) }}" defer></script>
@endpush
