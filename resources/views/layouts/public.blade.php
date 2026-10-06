@extends('layouts.base')

@section('page')
    @if (session('toast'))
        <div class="toast" role="status" data-toast>{{ session('toast') }}</div>
    @endif
    <main id="main">
        @yield('content')
    </main>
@endsection
