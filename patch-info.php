<?php
$f = __DIR__ . '/resources/views/user-app/sampah.blade.php';
$s = @file_get_contents($f);
if ($s === false) { exit("File tidak ditemukan: $f\n"); }
if (strpos($s, 'popup-info-sampah') !== false) { exit("Sudah pernah di-patch, tidak ada yang diubah.\n"); }

$blok = <<<'EOT'

{{-- Penjelasan jenis sampah (ikon i): kartu kecil, hanya menampilkan istilah khusus dari kamus --}}
@php
    $petaIstilah = [];
    foreach (($istilah ?? []) as $ist) { $petaIstilah[mb_strtolower(trim($ist->istilah))] = $ist->penjelasan; }
@endphp
<style>
  #panel-info, #panel-info-latar { display:none !important; }
</style>
<div id="popup-info-sampah-latar" style="display:none; position:fixed; inset:0; z-index:9998; background:rgba(0,0,0,.25);"></div>
<div id="popup-info-sampah" role="dialog" aria-modal="true" aria-labelledby="popup-info-nama"
     style="display:none; position:fixed; left:50%; transform:translateX(-50%); bottom:84px; width:calc(100% - 32px); max-width:340px; max-height:45vh; overflow-y:auto; z-index:9999; background:#fffbeb; border:1px solid #fde68a; border-radius:16px; padding:12px 14px 14px; box-shadow:0 8px 28px rgba(0,0,0,.22);">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
    <h6 id="popup-info-nama" style="font-weight:700; font-size:14px; line-height:1.3; margin:0;"></h6>
    <button type="button" id="popup-info-tutup" aria-label="Tutup" style="flex:none; border:0; background:#fef3c7; border-radius:50%; width:28px; height:28px; padding:0; font-size:13px; line-height:28px;">✕</button>
  </div>
  <p id="popup-info-isi" style="font-size:13px; line-height:1.5; color:#374151; margin:8px 0 0;"></p>
</div>
<script>
(function(){
  var peta = @json($petaIstilah);
  var p = document.getElementById('popup-info-sampah'), lt = document.getElementById('popup-info-sampah-latar');
  function tutup(){ p.style.display='none'; lt.style.display='none'; }
  document.addEventListener('click', function(e){
    var b = e.target.closest ? e.target.closest('[data-desk]') : null;
    if (b) {
      e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
      var nama = b.getAttribute('data-nama') || '', desk = (b.getAttribute('data-desk') || '').trim();
      var extra = (peta[nama.trim().toLowerCase()] || '').trim();
      var isi = document.getElementById('popup-info-isi');
      document.getElementById('popup-info-nama').textContent = nama;
      // Utamakan istilah khusus; kalau tidak ada, pakai deskripsi biasa (tanpa dobel)
      isi.textContent = extra ? ('Istilah khusus: ' + extra) : (desk || 'Belum ada penjelasan untuk jenis sampah ini.');
      isi.style.color = extra ? '#92400e' : '#374151';
      p.scrollTop = 0; lt.style.display = 'block'; p.style.display = 'block';
      return;
    }
    if (e.target.id === 'popup-info-tutup' || e.target === lt) tutup();
  }, true);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') tutup(); });
})();
</script>

EOT;

// 1) Hapus bagian Istilah Khusus di dasar halaman (bila ada)
$nHapus = 0;
$s2 = preg_replace('/\s*<section[^>]*id="blok-istilah"[^>]*>.*?<\/section>/s', '', $s, 1, $nHapus);

// 2) Pasang popup baru sebelum @endsection terakhir
$pos = strrpos($s2, '@endsection');
if ($pos === false) { exit("@endsection tidak ditemukan, tidak ada yang diubah.\n"); }
$s2 = substr($s2, 0, $pos) . $blok . substr($s2, $pos);

copy($f, $f . '.bak-info');
file_put_contents($f, $s2);
echo "Selesai (bagian Istilah Khusus dihapus: $nHapus). Cadangan: sampah.blade.php.bak-info\n";