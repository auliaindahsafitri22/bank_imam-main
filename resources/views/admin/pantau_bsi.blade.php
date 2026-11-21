@extends('crudbooster::admin_template')
@section('content')
@php
    $kondisi = [
        'ok'                  => ['success', 'Sesuai'],
        'tanpa_kelurahan'     => ['danger',  'Kelurahan BSU belum diisi'],
        'kelurahan_tanpa_bsi' => ['warning', 'Kelurahan belum punya BSI'],
        'sumber_beda'         => ['warning', 'Harga dari BSI lain'],
    ];
    $perhatian = $ringkas['tanpa_kelurahan'] + $ringkas['kelurahan_tanpa_bsi'] + $ringkas['sumber_beda'];
    $bsiBisaTerima = array_values(array_filter($bsi, function ($b) { return $b->kelurahan_id && $b->jml_harga === 0; }));
    $bsiBerkelurahan = array_values(array_filter($bsi, function ($b) { return $b->kelurahan_id; }));
    $bsiPunyaHarga = array_values(array_filter($bsi, function ($b) { return $b->jml_harga > 0; }));
@endphp

<div class="callout callout-info">
  <b>Aturan:</b> satu akun BSI hanya memegang <b>satu kelurahan</b>, dan satu kelurahan hanya punya <b>satu BSI</b>.
  BSU memakai harga acuan dari BSI kelurahannya. Kelurahan yang belum punya BSI memakai <i>Harga Bersama</i> (daftar lama).
</div>

<div class="row">
  <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-sitemap"></i></span><div class="info-box-content"><span class="info-box-text">Akun BSI</span><span class="info-box-number">{{ count($bsi) }}</span></div></div></div>
  <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-building-o"></i></span><div class="info-box-content"><span class="info-box-text">Total BSU</span><span class="info-box-number">{{ $ringkas['bsu'] }}</span></div></div></div>
  <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">BSU sesuai</span><span class="info-box-number">{{ $ringkas['ok'] }}</span></div></div></div>
  <div class="col-sm-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-warning"></i></span><div class="info-box-content"><span class="info-box-text">Perlu perhatian</span><span class="info-box-number">{{ $perhatian }}</span></div></div></div>
</div>

{{-- 1. BSI dan kelurahannya --}}
<div class="box box-success">
  <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-sitemap"></i> BSI dan kelurahan yang dipegang</h3>
    <div class="box-tools"><a href="{{ CRUDBooster::adminPath('akun_bsi/add') }}" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Tambah akun BSI</a></div></div>
  <div class="box-body table-responsive">
  <table class="table table-striped table-bordered">
    <thead><tr><th>BSI</th><th>Kelurahan</th><th class="text-right">BSU tercakup</th><th class="text-right">Jenis harga (aktif/total)</th><th>Harga diperbarui</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($bsi as $b)
      <tr>
        <td><b>{{ $b->name }}</b><br><small class="text-muted">{{ $b->email }}</small></td>
        <td>@if($b->kelurahan)Kel. {{ $b->kelurahan }}@else<span class="label label-danger">Belum ada kelurahan</span>@endif</td>
        <td class="text-right">{{ $b->jml_bsu }}</td>
        <td class="text-right">{{ $b->jml_harga_aktif }} / {{ $b->jml_harga }}</td>
        <td>{{ $b->harga_terakhir ? date('d/m/Y H:i', strtotime($b->harga_terakhir)) : '-' }}</td>
        <td>@if($b->aktif)<span class="label label-success">Aktif</span>@else<span class="label label-default">Nonaktif</span>@endif</td>
        <td class="text-right" style="white-space:nowrap">
          <a class="btn btn-xs btn-info" href="{{ CRUDBooster::adminPath('bsi_harga') }}?bsi={{ $b->id }}"><i class="fa fa-line-chart"></i> Harga</a>
          <a class="btn btn-xs btn-primary" href="{{ CRUDBooster::adminPath('akun_bsi/edit/'.$b->id) }}"><i class="fa fa-pencil"></i> Ubah</a>
          <a class="btn btn-xs btn-default" href="{{ CRUDBooster::mainpath() }}?bsi={{ $b->id }}#bsu"><i class="fa fa-search"></i> BSU</a>
          <form method="post" action="{{ CRUDBooster::adminPath('akun_bsi/hapus') }}" style="display:inline" onsubmit="return confirm('Hapus akun BSI ini? Akun dinonaktifkan, kelurahannya dilepas, semua harga acuannya ikut dihapus dan BSU terkait diputus (harga nasabah tetap). Bisa dipulihkan.')">{{ csrf_field() }}<input type="hidden" name="id" value="{{ $b->id }}"><button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Hapus</button></form>
        </td>
      </tr>
    @empty
      <tr><td colspan="7" class="text-center text-muted">Belum ada akun BSI. Buat lewat tombol "Tambah akun BSI".</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>

@if(count($bsiDihapus))
<div class="box box-danger">
  <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-trash"></i> Akun BSI yang dihapus ({{ count($bsiDihapus) }})</h3></div>
  <div class="box-body table-responsive">
  <table class="table table-condensed table-bordered">
    <thead><tr><th>BSI</th><th>Kelurahan sebelumnya</th><th>Dihapus</th><th></th></tr></thead>
    <tbody>
    @foreach($bsiDihapus as $x)
      <tr style="color:#777"><td><s>{{ $x->name }}</s> <span class="label label-danger">DIHAPUS</span><br><small>{{ $x->email }}</small></td>
        <td>{{ $x->nama_kelurahan ? 'Kel. '.$x->nama_kelurahan : '-' }}</td>
        <td>{{ date('d/m/Y H:i', strtotime($x->dihapus_at)) }}{{ $x->penghapus ? ' oleh '.$x->penghapus : '' }}</td>
        <td class="text-right"><form method="post" action="{{ CRUDBooster::adminPath('akun_bsi/pulihkan') }}" style="display:inline">{{ csrf_field() }}<input type="hidden" name="id" value="{{ $x->id }}"><button class="btn btn-xs btn-success"><i class="fa fa-undo"></i> Pulihkan</button></form></td></tr>
    @endforeach
    </tbody>
  </table></div>
</div>
@endif

{{-- 2. BSU dan sumber harganya --}}
<div class="box box-default" id="bsu">
  <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-search"></i> Temukan BSU: kelurahan, BSI, dan sumber harga</h3></div>
  <div class="box-body">
    <form method="get" class="form-inline" style="margin-bottom:12px">
      <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama BSU / kelurahan / BSI…" style="min-width:260px">
      <select name="bsi" class="form-control" onchange="this.form.submit()">
        <option value="">Semua BSI</option>
        <option value="none" {{ $bsiF === 'none' ? 'selected' : '' }}>(Belum punya BSI)</option>
        @foreach($bsi as $b)<option value="{{ $b->id }}" {{ (string) $bsiF === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}{{ $b->kelurahan ? ' — Kel. '.$b->kelurahan : '' }}</option>@endforeach
      </select>
      <select name="kondisi" class="form-control" onchange="this.form.submit()">
        <option value="">Semua kondisi</option>
        @foreach($kondisi as $k => $v)<option value="{{ $k }}" {{ $kondF === $k ? 'selected' : '' }}>{{ $v[1] }}</option>@endforeach
      </select>
      <button class="btn btn-primary">Cari</button> <a href="{{ CRUDBooster::mainpath() }}" class="btn btn-default">Reset</a>
      <span class="text-muted">{{ count($bsu) }} dari {{ $totalBsu }} BSU</span>
    </form>
    <div class="table-responsive">
    <table class="table table-striped table-bordered table-condensed">
      <thead><tr><th>BSU</th><th>Kelurahan</th><th>BSI kelurahan (sumber seharusnya)</th><th>Harga yang dipakai BSU berasal dari</th><th>Kondisi</th><th></th></tr></thead>
      <tbody>
      @forelse($bsu as $u)
        <tr>
          <td><b>{{ $u->name }}</b>@if(!$u->aktif) <span class="label label-default">Nonaktif</span>@endif</td>
          <td>
            @if($u->kelurahan)Kel. {{ $u->kelurahan }}
            @else<span class="text-danger">belum diisi</span>@if($u->kelurahan_teks)<br><small class="text-muted">catatan lama: {{ $u->kelurahan_teks }}</small>@endif @endif
          </td>
          <td>@if($u->bsi_nama){{ $u->bsi_nama }}@if($u->bsi_aktif === false) <span class="label label-default">nonaktif</span>@endif
              @else<span class="text-muted">— (memakai Harga Bersama)</span>@endif</td>
          <td>
            @forelse($u->sumber as $s)
              <div>{{ $s['nama'] }} <small class="text-muted">({{ $s['n'] }} jenis)</small></div>
            @empty<span class="text-muted">belum menghubungkan jenis sampah</span>@endforelse
          </td>
          <td><span class="label label-{{ $kondisi[$u->kondisi][0] }}">{{ $kondisi[$u->kondisi][1] }}</span></td>
          <td class="text-right"><a class="btn btn-xs btn-primary" href="{{ CRUDBooster::adminPath('users/edit/'.$u->id) }}"><i class="fa fa-pencil"></i> Atur kelurahan</a></td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center text-muted">Tidak ada BSU yang cocok.</td></tr>
      @endforelse
      </tbody>
    </table></div>
  </div>
</div>

{{-- 3. Kelurahan tanpa BSI --}}
<div class="box box-warning">
  <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-map-marker"></i> Kelurahan yang belum punya BSI ({{ count($kelurahanTanpaBsi) }})</h3></div>
  <div class="box-body">
  @if(count($kelurahanTanpaBsi))
    <p class="text-muted">BSU di kelurahan ini memakai Harga Bersama sampai akun BSI-nya dibuat.</p>
    @foreach($kelurahanTanpaBsi as $k)
      <span class="label label-default" style="display:inline-block;margin:2px;font-size:90%;padding:6px 8px">{{ $k->nama }} <span class="badge">{{ $k->jml_bsu }} BSU</span></span>
    @endforeach
  @else
    <span class="text-success"><i class="fa fa-check"></i> Semua kelurahan sudah punya BSI.</span>
  @endif
  </div>
</div>

{{-- 4. Harga Bersama dan alat migrasi --}}
<div class="box box-info">
  <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-exchange"></i> Daftar harga lama ("Harga Bersama") dan penyalinan</h3></div>
  <div class="box-body">
    <p>Harga Bersama: <b>{{ $bersama->jml }}</b> jenis ({{ $bersama->aktif }} aktif), dipakai <b>{{ $bersama->bsu_terhubung }}</b> BSU
      @if($bersama->terakhir)· diperbarui {{ date('d/m/Y H:i', strtotime($bersama->terakhir)) }}@endif.
      Harga Bersama hanya melayani BSU yang kelurahannya belum punya BSI.</p>
    <div class="row">
      <div class="col-md-6">
        <h4>Serahkan Harga Bersama ke satu BSI</h4>
        <p class="text-muted">Dipakai sekali untuk memindahkan daftar lama ke BSI pertama. Harga, riwayat, dan sambungan BSU tidak berubah, hanya pemiliknya. Hanya BSI yang belum punya daftar sendiri.</p>
        @if($bersama->jml > 0 && count($bsiBisaTerima))
        <form method="post" action="{{ CRUDBooster::mainpath('serahkan') }}" class="form-inline" onsubmit="return confirm('Serahkan seluruh Harga Bersama ke BSI ini? Setelah itu BSU di kelurahan BSI lain akan memakai daftar BSI mereka sendiri.')">{{ csrf_field() }}
          <select name="bsi_id" class="form-control" required>
            <option value="">Pilih BSI…</option>
            @foreach($bsiBisaTerima as $b)<option value="{{ $b->id }}">{{ $b->name }} — Kel. {{ $b->kelurahan }}</option>@endforeach
          </select>
          <button class="btn btn-warning"><i class="fa fa-share"></i> Serahkan</button>
        </form>
        @else
          <span class="text-muted">{{ $bersama->jml === 0 ? 'Tidak ada Harga Bersama untuk diserahkan.' : 'Tidak ada BSI berkelurahan yang daftarnya masih kosong.' }}</span>
        @endif
      </div>
      <div class="col-md-6">
        <h4>Salin daftar harga ke BSI</h4>
        <p class="text-muted">Titik awal untuk BSI baru. Nama yang sudah ada dilewati. BSU belum dipindah otomatis; BSU menghubungkan ulang di menu Harga &amp; Margin.</p>
        @if(count($bsiBerkelurahan))
        <form method="post" action="{{ CRUDBooster::mainpath('salin') }}" class="form-inline" onsubmit="return confirm('Salin daftar harga ke BSI tujuan?')">{{ csrf_field() }}
          <select name="dari" class="form-control" required>
            <option value="">Dari…</option>
            <option value="bersama">{{ \App\Services\WilayahBsiService::NAMA_BERSAMA }}</option>
            @foreach($bsiPunyaHarga as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
          </select>
          <select name="bsi_id" class="form-control" required>
            <option value="">Ke BSI…</option>
            @foreach($bsiBerkelurahan as $b)<option value="{{ $b->id }}">{{ $b->name }} — Kel. {{ $b->kelurahan }}</option>@endforeach
          </select>
          <button class="btn btn-primary"><i class="fa fa-copy"></i> Salin</button>
        </form>
        @else
          <span class="text-muted">Belum ada BSI yang punya kelurahan.</span>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
