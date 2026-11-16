@extends('crudbooster::admin_template')
@section('content')
<p><a href="{{ CRUDBooster::mainpath() }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali ke Data Sampah</a></p>

<div class="box box-success">
 <div class="box-header with-border"><h3 class="box-title">Pratinjau impor katalog dari {{ $pratinjau['sumber']['nama'] }} — belum disimpan</h3></div>
 <div class="box-body">
  <div class="row">
   <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-plus"></i></span><div class="info-box-content"><span class="info-box-text">Jenis akan ditambah</span><span class="info-box-number">{{ count($pratinjau['baru']) }}</span></div></div></div>
   <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-folder-open"></i></span><div class="info-box-content"><span class="info-box-text">Kategori baru</span><span class="info-box-number">{{ count($pratinjau['kategori_baru']) }}</span></div></div></div>
   <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-minus"></i></span><div class="info-box-content"><span class="info-box-text">Dilewati (sudah ada)</span><span class="info-box-number">{{ count($pratinjau['sudah_ada']) }}</span></div></div></div>
   <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-gray"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">Total di Mercu Buana</span><span class="info-box-number">{{ $pratinjau['total_sumber'] }}</span></div></div></div>
  </div>
  <ul class="text-muted">
   <li>Yang disalin: kategori, nama jenis, harga per kg, poin per kg, dan deskripsi.</li>
   <li>Jenis yang namanya sudah ada di akun Anda dilewati, jadi harga yang sudah Anda atur tidak berubah.</li>
   <li>Gambar tidak ikut disalin. Setelah impor, harga dan poin tetap bisa diubah per jenis di Data Sampah.</li>
  </ul>

  @if(count($pratinjau['baru']))
  <div class="table-responsive" style="max-height:420px;overflow:auto">
   <table class="table table-bordered table-striped table-condensed">
    <thead><tr><th>#</th><th>Kategori</th><th>Jenis sampah</th><th class="text-right">Harga / kg</th><th class="text-right">Poin / kg</th></tr></thead>
    <tbody>
    @foreach($pratinjau['baru'] as $i => $b)
     <tr><td>{{ $i + 1 }}</td><td>{{ $b['kategori'] !== '' ? $b['kategori'] : '-' }}</td><td>{{ $b['nama'] }}</td><td class="text-right">Rp {{ number_format($b['harga'], 0, ',', '.') }}</td><td class="text-right">{{ number_format($b['poin'], 0, ',', '.') }}</td></tr>
    @endforeach
    </tbody>
   </table>
  </div>
  <form method="post" action="{{ CRUDBooster::mainpath('impor-katalog') }}" onsubmit="this.querySelector('button').disabled=true;">{{ csrf_field() }}
   <button class="btn btn-success btn-lg"><i class="fa fa-download"></i> Impor {{ count($pratinjau['baru']) }} jenis sampah ke akun saya</button>
  </form>
  @else
  <div class="alert alert-info" style="margin-bottom:0">Semua jenis sampah dari {{ $pratinjau['sumber']['nama'] }} sudah ada di akun Anda. Tidak ada yang perlu diimpor.</div>
  @endif

  @if(count($pratinjau['sudah_ada']))
  <hr><p><b>Dilewati karena sudah ada ({{ count($pratinjau['sudah_ada']) }}):</b> <span class="text-muted">{{ implode(', ', $pratinjau['sudah_ada']) }}</span></p>
  @endif
 </div>
</div>
@endsection
