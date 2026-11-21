@extends('crudbooster::admin_template')
@section('content')
{!! $bannerBsi ?? '' !!}
<style>
 #tb-cepat input.form-control{min-width:110px} #tb-cepat tr.kat td{background:#e8f5ee;font-weight:700} #tb-cepat tr.terisi td{background:#f6fff9}
 .ref{font-size:.85em;color:#33414d} .stik{position:sticky;top:0;z-index:5;background:#fff;padding:8px 0;border-bottom:1px solid #ddd}
</style>
<p><a href="{{ CRUDBooster::mainpath() }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
 <a href="{{ CRUDBooster::mainpath('impor') }}" class="btn btn-warning"><i class="fa fa-upload"></i> Impor dari file</a>
 <a href="{{ CRUDBooster::mainpath('template') }}" class="btn btn-info"><i class="fa fa-download"></i> Unduh template Excel</a></p>

<div class="callout callout-info">
 <p>Daftar di bawah dibuat dari <b>kategori dan jenis sampah yang sudah ada</b> (daftar BSI dan yang sudah dipakai BSU). Cukup isi kolom <b>Harga BSI</b>; kolom <b>Acuan di BSU</b> membantu menentukan harga, dan tombol <i>Pakai rata-rata</i> mengisinya otomatis.
 Baris yang dikosongkan tidak diubah. Harga di bawah dihitung per kg.</p>
 <p>{{ $total }} jenis tercatat, <b>{{ $belum }}</b> belum punya harga acuan.</p>
</div>

<form method="post" action="{{ CRUDBooster::mainpath('isi-cepat') }}" id="f">{{ csrf_field() }}
<div class="box box-success">
 <div class="box-header with-border stik">
  <div class="row">
   <div class="col-sm-5"><input type="search" id="cari" class="form-control" placeholder="Cari jenis atau kategori…"></div>
   <div class="col-sm-4"><label style="font-weight:normal;margin-top:7px"><input type="checkbox" id="belum"> Hanya yang belum diisi</label></div>
   <div class="col-sm-3 text-right"><span class="label label-success" id="hitung" style="font-size:100%">0 diisi/diubah</span></div>
  </div>
 </div>
 <div class="box-body table-responsive">
 <table class="table table-bordered" id="tb-cepat">
  <thead><tr><th>Jenis sampah</th><th style="width:150px">Harga BSI/kg (Rp)</th><th style="width:100px">Poin/kg</th><th>Acuan di BSU</th><th class="text-center" style="width:70px">Aktif</th></tr></thead>
  <tbody>
  @php($n = 0)
  @foreach($kelompok as $kat => $daftar)
   <tr class="kat" data-kat="{{ mb_strtolower($kat) }}"><td colspan="5">{{ $kat }} <small class="text-muted">({{ count($daftar) }})</small></td></tr>
   @foreach($daftar as $i)
   <tr class="baris" data-t="{{ mb_strtolower($i['nama'].' '.$kat) }}" data-kat="{{ mb_strtolower($kat) }}">
    <td><b>{{ $i['nama'] }}</b> @if(!$i['ada'])<span class="label label-default">dari katalog BSU</span>@endif
      <input type="hidden" name="row[{{ $n }}][master_id]" value="{{ $i['master_id'] }}"><input type="hidden" name="row[{{ $n }}][nama]" value="{{ $i['nama'] }}"><input type="hidden" name="row[{{ $n }}][kategori]" value="{{ $i['kategori'] }}"></td>
    <td><input type="text" inputmode="numeric" class="form-control hg" name="row[{{ $n }}][harga]" value="{{ $i['harga'] !== null ? $i['harga'] : '' }}" data-asli="{{ $i['harga'] !== null ? $i['harga'] : '' }}" placeholder="kosong = lewati" aria-label="Harga {{ $i['nama'] }}"></td>
    <td><input type="text" inputmode="numeric" class="form-control pn" name="row[{{ $n }}][poin]" value="{{ $i['poin'] }}" data-asli="{{ $i['poin'] }}" aria-label="Poin {{ $i['nama'] }}"></td>
    <td class="ref">@if($i['ref'])Rp {{ number_format($i['ref']['min'],0,',','.') }}–{{ number_format($i['ref']['maks'],0,',','.') }}<br>rata-rata <b>Rp {{ number_format($i['ref']['rata'],0,',','.') }}</b> · {{ $i['ref']['jml'] }} BSU
        <br><button type="button" class="btn btn-xs btn-default pakai" data-rata="{{ $i['ref']['rata'] }}">Pakai rata-rata</button>@else <span class="text-muted">—</span>@endif</td>
    <td class="text-center"><input type="checkbox" name="row[{{ $n }}][aktif]" value="1" {{ $i['aktif'] ? 'checked' : '' }} style="width:20px;height:20px" aria-label="Aktif {{ $i['nama'] }}"></td>
   </tr>
   @php($n++)
   @endforeach
  @endforeach
  </tbody>
  <tbody id="baru"></tbody>
 </table>
 <button type="button" class="btn btn-default" id="tambah"><i class="fa fa-plus"></i> Tambah jenis sampah baru</button>
 </div>
 <div class="box-footer"><button class="btn btn-success btn-lg" id="simpan"><i class="fa fa-save"></i> Simpan semua</button>
  <span class="text-muted"> Harga yang berubah otomatis diteruskan ke BSU yang terhubung; setiap BSU menerima satu notifikasi ringkasan.</span></div>
</div>
</form>
<datalist id="dl-kat">@foreach($kategori as $k)<option value="{{ $k }}">@endforeach</datalist>
@endsection
@push('bottom')
<script>
(function(){
 var tb=document.getElementById('tb-cepat'), nxt={{ $n }}, kotor=false;
 function bersih(v){return String(v==null?'':v).replace(/[^0-9]/g,'');}
 function baris(){return [].slice.call(tb.querySelectorAll('tr.baris'));}
 function hitung(){
   var c=0; baris().forEach(function(tr){ var h=tr.querySelector('.hg'), p=tr.querySelector('.pn'); var a=h.value.trim()!==h.dataset.asli||(p.value.trim()!==p.dataset.asli&&h.value.trim()!=='');
     if(tr.classList.contains('baru')){ a=h.value.trim()!==''; } tr.classList.toggle('terisi',a); if(a)c++; });
   document.getElementById('hitung').textContent=c+' diisi/diubah'; return c;
 }
 function saring(){
   var t=document.getElementById('cari').value.trim().toLowerCase(), b=document.getElementById('belum').checked, tampil={};
   baris().forEach(function(tr){ var ok=(!t||tr.dataset.t.indexOf(t)>-1)&&(!b||tr.querySelector('.hg').value.trim()===''); tr.style.display=ok?'':'none'; if(ok)tampil[tr.dataset.kat]=1; });
   [].forEach.call(tb.querySelectorAll('tr.kat'),function(k){k.style.display=tampil[k.dataset.kat]?'':'none';});
 }
 tb.addEventListener('input',function(e){ kotor=true; hitung(); });
 tb.addEventListener('click',function(e){ var b=e.target.closest('.pakai'); if(b){ var tr=b.closest('tr'); tr.querySelector('.hg').value=b.dataset.rata; kotor=true; hitung(); saring(); }
   var h=e.target.closest('.hapus'); if(h){ h.closest('tr').remove(); hitung(); } });
 document.getElementById('cari').addEventListener('input',saring); document.getElementById('belum').addEventListener('change',saring);
 document.getElementById('tambah').onclick=function(){
   var tr=document.createElement('tr'); tr.className='baris baru'; tr.dataset.t=''; tr.dataset.kat='';
   tr.innerHTML='<td><input type="text" class="form-control" name="row['+nxt+'][nama]" placeholder="Nama jenis sampah" maxlength="120" required><input type="text" class="form-control" style="margin-top:4px" name="row['+nxt+'][kategori]" list="dl-kat" placeholder="Kategori (pilih / ketik baru)" maxlength="80"><input type="hidden" name="row['+nxt+'][master_id]" value=""></td>'+
     '<td><input type="text" inputmode="numeric" class="form-control hg" name="row['+nxt+'][harga]" data-asli="" placeholder="harga"></td><td><input type="text" inputmode="numeric" class="form-control pn" name="row['+nxt+'][poin]" data-asli="" value="0"></td>'+
     '<td><button type="button" class="btn btn-xs btn-danger hapus">Hapus baris</button></td><td class="text-center"><input type="checkbox" name="row['+nxt+'][aktif]" value="1" checked style="width:20px;height:20px"></td>';
   document.getElementById('baru').appendChild(tr); nxt++; tr.querySelector('input').focus();
 };
 document.getElementById('f').addEventListener('submit',function(e){
   var n=hitung(); if(n<1){ e.preventDefault(); alert('Belum ada harga yang diisi atau diubah.'); return; }
   if(!confirm('Simpan '+n+' jenis sampah? Harga yang berubah akan diteruskan ke BSU yang terhubung.')){ e.preventDefault(); return; }
   kotor=false; document.getElementById('simpan').disabled=true;
 });
 window.addEventListener('beforeunload',function(e){ if(kotor){ e.preventDefault(); e.returnValue=''; }});
 hitung();
})();
</script>
@endpush
