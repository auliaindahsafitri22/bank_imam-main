@extends('crudbooster::admin_template')
@section('content')
<div class="callout callout-info"><p>Atur <b>ukuran huruf</b>, <b>ukuran ikon</b> (kecil, sedang, besar), dan <b>kontras warna</b> agar nyaman dibaca. Perubahan langsung berlaku di seluruh halaman CMS dan tersimpan di peramban ini.</p></div>
<div id="a11y-full"></div>
@endsection
@push('bottom')
<script>window.A11Y_NO_WIDGET = true; A11Y.panel(document.getElementById('a11y-full'));</script>
@endpush
