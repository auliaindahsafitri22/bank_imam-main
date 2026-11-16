@extends('crudbooster::admin_template')
@section('content')

    {{-- Ringkasan ditambahkan di halaman awal (dashboard); widget statistik bawaan tetap tampil. --}}
    @php
        $__admin = trim(config('crudbooster.ADMIN_PATH') ?: 'admin', '/');
        $__awal = request()->is($__admin) || request()->is($__admin . '/statistic_builder/show/dashboard*');
        $__ringkasan = $__awal ? app(\App\Services\RingkasanAdmin::class)->untukSaya() : null;
    @endphp
    @if($__ringkasan)
        @include($__ringkasan['view'], $__ringkasan['data'])
    @endif

    @include('crudbooster::statistic_builder.index')

@endsection
