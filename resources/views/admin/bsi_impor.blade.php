@extends('crudbooster::admin_template')
@section('content')
{!! $bannerBsi ?? '' !!}
<p><a href="{{ CRUDBooster::mainpath() }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
 <a href="{{ CRUDBooster::mainpath('isi-cepat') }}" class="btn btn-primary"><i class="fa fa-table"></i> Isi harga cepat (tabel)</a></p>

@if(!$pratinjau)
<div class="row">
 <div class="col-md-6"><div class="box box-warning"><div class="box-header"><h3 class="box-title">1. Unduh template</h3></div><div class="box-body">
  <p>Template Excel sudah berisi <b>semua kategori dan jenis sampah yang ada</b>. Anda tinggal mengisi kolom <b>Harga BSI per kg</b> (dan poin bila perlu), lalu mengunggahnya kembali.</p>
  <a href="{{ CRUDBooster::mainpath('template') }}" class="btn btn-info btn-lg"><i class="fa fa-download"></i> Unduh template (Excel)</a>
  <hr><b>Aturan pengisian</b>
  <ul>
   <li>Kolom wajib: <b>Jenis Sampah</b> dan <b>Harga</b>. Kolom lain boleh dikosongkan.</li>
   <li>Harga boleh ditulis <code>3500</code>, <code>3.500</code>, atau <code>Rp 3.500</code>.</li>
   <li>Harga <b>kosong</b> = baris dilewati. Harga <b>0</b> = benar-benar gratis.</li>
   <li>Status: <code>Aktif</code> atau <code>Nonaktif</code>. Nama jenis yang sama dianggap jenis yang sama (huruf besar/kecil diabaikan).</li>
   <li>Format: .xlsx, .xls, atau .csv (pemisah koma/titik koma). Maks. 2 MB dan {{ 1000 }} baris.</li>
  </ul>
 </div></div></div>
 <div class="col-md-6"><div class="box box-success"><div class="box-header"><h3 class="box-title">2. Unggah file</h3></div><div class="box-body">
  <form method="post" enctype="multipart/form-data" action="{{ CRUDBooster::mainpath('impor') }}">{{ csrf_field() }}
   <div class="form-group"><label>Pilih file harga</label><input type="file" name="berkas" class="form-control" accept=".xlsx,.xls,.csv" required></div>
   <button class="btn btn-success btn-lg"><i class="fa fa-search"></i> Periksa file (pratinjau)</button>
   <p class="text-muted" style="margin-top:8px">Belum ada yang disimpan pada langkah ini. Anda akan melihat pratinjau terlebih dahulu.</p>
  </form>
 </div></div></div>
</div>
@else
@php($r = $pratinjau['ringkas'])
<div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Pratinjau — belum disimpan</h3></div><div class="box-body">
 <p style="font-size:1.1em">
  <span class="label label-success">{{ $r['baru'] }} jenis baru</span>
  <span class="label label-warning">{{ $r['ubah'] }} diubah</span>
  <span class="label label-default">{{ $r['sama'] }} tanpa perubahan</span>
  <span class="label label-info">{{ $r['kosong'] }} dilewati (harga kosong)</span>
  <span class="label label-danger">{{ $r['error'] }} bermasalah</span></p>
 @if($r['error'])<div class="alert alert-danger">Baris bermasalah <b>tidak akan diterapkan</b>. Perbaiki di file lalu unggah ulang, atau lanjutkan untuk baris yang valid saja.</div>@endif
 <div class="table-responsive"><table class="table table-bordered table-condensed">
  <thead><tr><th>Baris</th><th>Status</th><th>Kategori</th><th>Jenis sampah</th><th class="text-right">Harga lama</th><th class="text-right">Harga baru</th><th class="text-right">Poin</th><th>Keterangan</th></tr></thead><tbody>
  @foreach($pratinjau['baris'] as $b)
   @php($warna = ['baru'=>'success','ubah'=>'warning','sama'=>'','kosong'=>'info','error'=>'danger'][$b['status']])
   <tr class="{{ $warna }}"><td>{{ $b['no'] }}</td>
    <td>{{ ['baru'=>'Baru','ubah'=>'Diubah','sama'=>'Sama','kosong'=>'Dilewati','error'=>'Error'][$b['status']] }}</td>
    <td>{{ $b['kategori'] }}</td><td>{{ $b['nama'] }}</td>
    <td class="text-right">{{ $b['harga_lama'] !== null ? 'Rp '.number_format($b['harga_lama'],0,',','.') : '—' }}</td>
    <td class="text-right"><b>{{ $b['harga'] !== null ? 'Rp '.number_format($b['harga'],0,',','.') : '—' }}</b></td>
    <td class="text-right">{{ $b['poin'] !== null ? $b['poin'] : '' }}</td><td>{{ $b['pesan'] }}</td></tr>
  @endforeach
  </tbody></table></div>
 @if($r['baru'] + $r['ubah'] > 0)
 <form method="post" action="{{ CRUDBooster::mainpath('impor-terapkan') }}">{{ csrf_field() }}<input type="hidden" name="token" value="{{ $token }}">
  <div class="checkbox"><label><input type="checkbox" name="lanjut_kirim" value="1" checked> Setelah diterapkan, lanjut ke halaman <b>kirim pengumuman</b> ke BSU</label></div>
  <button class="btn btn-success btn-lg" onclick="return confirm('Terapkan {{ $r['baru'] + $r['ubah'] }} perubahan?')"><i class="fa fa-check"></i> Terapkan {{ $r['baru'] + $r['ubah'] }} perubahan</button>
  <a href="{{ CRUDBooster::mainpath('impor') }}" class="btn btn-default btn-lg">Batal</a>
 </form>
 @else<a href="{{ CRUDBooster::mainpath('impor') }}" class="btn btn-default btn-lg">Unggah file lain</a>@endif
</div></div>
@endif
@endsection
