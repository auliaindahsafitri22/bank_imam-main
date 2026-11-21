@extends('crudbooster::admin_template')
@section('content')

    {{-- Ringkasan (dipindahkan dari menu Ringkasan) di atas dashboard bawaan --}}
    @if (!empty($ringkasanDashboard))
        {!! $ringkasanDashboard !!}
    @endif

    @include('crudbooster::statistic_builder.index')

@endsection
