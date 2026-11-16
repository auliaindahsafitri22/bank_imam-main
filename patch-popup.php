<?php
$f = __DIR__ . '/resources/views/user-app/sampah.blade.php';
$s = @file_get_contents($f);
if ($s === false) { exit("File tidak ditemukan: $f\n"); }

$blok = <<<'EOT'

{{-- Penjelasan jenis sampah (ikon i): hanya "Apa itu?" --}}
<style>
  #panel-info, #panel-info-latar { display:none !important; }
</style>
<div id="popup-info-sampah-latar" style="display:none; position:fixed; inset:0; z-index:9998; background:rgba(0,0,0,.25);"></div>
<div id="popup-info-sampah" role="dialog" aria-modal="true" aria-labelledby="popup-info-nama"
     style="display:none; position:fixed; left:50%; transform:translateX(-50%); bottom:84px; width:calc(100% - 32px); max-width:340px; max-height:45vh; overflow-y:auto; z-index:9999; background:#fffbeb; border:1px solid #fde68a; border-radius:16px; padding:14px 16px 16px; box-shadow:0 8px 28px rgba(0,0,0,.22);">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
    <h6 id="popup-info-nama" style="font-weight:700; font-size:15px; line-height:1.3; margin:0;"></h6>
    <button type="button" id="popup-info-tutup" aria-label="Tutup" style="flex:none; border:0; background:#fef3c7; border-radius:50%; width:28px; height:28px; padding:0; font-size:13px; line-height:28px;">✕</button>
  </div>
  <span id="popup-info-kat" style="display:none; margin-top:8px; background:#fde68a; color:#92400e; border-radius:999px; padding:2px 10px; font-size:12px;"></span>
  <div style="margin-top:12px; font-size:11px; font-weight:700; letter-spacing:.04em; color:#92400e;">APA ITU?</div>
  <p id="popup-info-isi" style="font-size:13px; line-height:1.5; color:#374151; margin:4px 0 0;"></p>
</div>
<script>
(function(){
  var p = document.getElementById('popup-info-sampah'), lt = document.getElementById('popup-info-sampah-latar');
  function tutup(){ p.style.display='none'; lt.style.display='none'; }
  document.addEventListener('click', function(e){
    var b = e.target.closest ? e.target.closest('[data-desk]') : null;
    if (b) {
      e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
      var nama = b.getAttribute('data-nama') || '';
      var desk = (b.getAttribute('data-desk') || '').trim();
      var kat  = (b.getAttribute('data-kategori') || '').trim();
      document.getElementById('popup-info-nama').textContent = nama;
      document.getElementById('popup-info-isi').textContent = desk || 'Belum ada penjelasan untuk jenis sampah ini.';
      var k = document.getElementById('popup-info-kat');
      if (kat) { k.textContent = kat; k.style.display = 'inline-block'; } else { k.style.display = 'none'; }
      p.scrollTop = 0; lt.style.display = 'block'; p.style.display = 'block';
      return;
    }
    if (e.target.id === 'popup-info-tutup' || e.target === lt) tutup();
  }, true);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') tutup(); });
})();
</script>

EOT;

// 1) Hapus popup lama (kalau ada)
$nLama = 0;
$s2 = preg_replace('/\s*\{\{--\s*Penjelasan jenis sampah.*?<\/script>\s*/s', "\n", $s, 1, $nLama);
if ($nLama === 0 && strpos($s, 'id="popup-info-sampah"') !== false) {
    exit("Ada popup lama tapi bentuknya tidak dikenali. Kirim isi sampah.blade.php supaya saya sesuaikan.\n");
}

// 2) Hapus bagian Istilah Khusus di dasar halaman (bila ada)
$nHapus = 0;
$s2 = preg_replace('/\s*<section[^>]*id="blok-istilah"[^>]*>.*?<\/section>/s', '', $s2, 1, $nHapus);

// 3) Pasang popup baru sebelum @endsection terakhir
$pos = strrpos($s2, '@endsection');
if ($pos === false) { exit("@endsection tidak ditemukan, tidak ada yang diubah.\n"); }
$s2 = substr($s2, 0, $pos) . $blok . substr($s2, $pos);

copy($f, $f . '.bak-apaitu');
file_put_contents($f, $s2);
echo "Selesai (popup lama diganti: $nLama, Istilah Khusus dihapus: $nHapus). Cadangan: sampah.blade.php.bak-apaitu\n";