@extends('crudbooster::admin_template')
@section('content')
<div class="box box-{{ $bahaya ?? true ? 'danger' : 'success' }}">
  <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-{{ $ikon ?? 'trash' }}"></i> {{ $judul }}</h3></div>
  <form method="post" action="{{ $aksi }}">{{ csrf_field() }}<input type="hidden" name="id" value="{{ $id }}">
  <div class="box-body">
    @foreach($rincian as $baris)<p>{!! $baris !!}</p>@endforeach
  </div>
  <div class="box-footer">
    <a href="{{ $kembali }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Batal</a>
    <button class="btn btn-{{ $bahaya ?? true ? 'danger' : 'success' }}"><i class="fa fa-{{ $ikon ?? 'trash' }}"></i> {{ $tombol }}</button>
  </div>
  </form>
</div>
@endsection
