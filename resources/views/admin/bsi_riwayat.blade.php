@extends('crudbooster::admin_template')
@section('content')
{!! $bannerBsi ?? '' !!}
<p><a href="{{ $kembali }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a></p>
<div class="box box-default"><div class="box-header"><h3 class="box-title">Riwayat harga acuan BSI{{ $judul ? ': '.$judul : '' }}</h3></div>
<div class="box-body table-responsive">
<table class="table table-striped table-bordered">
<thead><tr><th>Tanggal</th><th>Jenis Sampah</th><th class="text-right">Harga Lama</th><th class="text-right">Harga Baru</th><th>Perubahan</th><th>Oleh</th></tr></thead>
<tbody>
@forelse($rows as $r)
<tr><td>{{ date('d/m/Y', strtotime($r->tanggal)) }}</td><td>@if($r->dihapus_at)<s>{{ $r->nama }}</s> <span class="label label-danger" title="Dihapus {{ date('d/m/Y H:i', strtotime($r->dihapus_at)) }}{{ $r->penghapus ? ' oleh '.$r->penghapus : '' }}">DIHAPUS {{ date('d/m/Y', strtotime($r->dihapus_at)) }}</span>@else{{ $r->nama }}@endif</td>
<td class="text-right">{{ $r->harga_lama === null ? '-' : 'Rp '.number_format($r->harga_lama,0,',','.') }}</td>
<td class="text-right">Rp {{ number_format($r->harga_baru,0,',','.') }}</td>
<td>@if($r->harga_lama === null)<span class="label label-default">Harga awal</span>
@elseif($r->harga_baru > $r->harga_lama)<span class="label label-success">▲ naik Rp {{ number_format($r->harga_baru-$r->harga_lama,0,',','.') }}</span>
@else<span class="label label-danger">▼ turun Rp {{ number_format($r->harga_lama-$r->harga_baru,0,',','.') }}</span>@endif</td>
<td>{{ $r->pengubah ?: '-' }}</td></tr>
@empty
<tr><td colspan="6" class="text-center text-muted">Belum ada riwayat.</td></tr>
@endforelse
</tbody></table></div></div>
@endsection
