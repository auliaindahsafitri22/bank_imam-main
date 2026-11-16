<?php
$f = __DIR__ . '/resources/views/user-app/sampah.blade.php';
$s = @file_get_contents($f);
if ($s === false) { exit("File tidak ditemukan: $f\n"); }
if (strpos($s, 'popup-apaitu') !== false) { exit("Popup baru sudah terpasang, tidak ada yang diubah.\n"); }

$blok = <<<'EOT'

{{-- Popup jenis sampah (ikon i): judul, badge kategori, lalu langsung "Apa itu?" --}}
<div id="popup-apaitu-latar" style="display:none; position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,.25);"></div>
<div id="popup-apaitu" role="dialog" aria-modal="true" aria-labelledby="popup-apaitu-nama"
     style="display:none; position:fixed; left:50%; transform:translateX(-50%); bottom:84px; width:calc(100% - 32px); max-width:340px; max-height:45vh; overflow-y:auto; z-index:10001; background:#fffbeb; border:1px solid #fde68a; border-radius:16px; padding:14px 16px 16px; box-shadow:0 8px 28px rgba(0,0,0,.22);">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
    <h6 id="popup-apaitu-nama" style="font-weight:700; font-size:15px; line-height:1.3; margin:0;"></h6>
    <button type="button" id="popup-apaitu-tutup" aria-label="Tutup" style="flex:none; border:0; background:#fef3c7; border-radius:50%; width:28px; height:28px; padding:0; font-size:13px; line-height:28px;">&#10005;</button>
  </div>
  <span id="popup-apaitu-kat" style="display:none; margin-top:8px; background:#fde68a; color:#92400e; border-radius:999px; padding:2px 10px; font-size:12px;"></span>
  <div style="margin-top:12px; font-size:11px; font-weight:700; letter-spacing:.04em; color:#92400e;">APA ITU?</div>
  <p id="popup-apaitu-isi" style="font-size:13px; line-height:1.5; color:#374151; margin:4px 0 0;"></p>
</div>
<script>
(function(){
  var p = document.getElementById('popup-apaitu'), lt = document.getElementById('popup-apaitu-latar');
  function tutup(){ p.style.display = 'none'; lt.style.display = 'none'; }

  function cariKategori(b){
    var k = b.getAttribute('data-kategori') || b.getAttribute('data-kat') || b.getAttribute('data-kategori-nama') || '';
    if (k) return k.trim();
    var el = b;
    for (var i = 0; i < 6 && el && el.parentElement; i++) {
      el = el.parentElement;
      var c = el.querySelector('[class*="badge"],[class*="kategori"],[class*="chip"],[class*="pill"]');
      if (c && !c.contains(b)) {
        var t = (c.textContent || '').trim();
        if (t.length > 0 && t.length <= 30 && /[A-Za-z]{3,}/.test(t) && t.indexOf('Rp') === -1) return t;
      }
    }
    return '';
  }

  window.addEventListener('click', function(e){
    var b = e.target.closest ? e.target.closest('[data-desk]') : null;
    if (b) {
      e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
      var nama = b.getAttribute('data-nama') || b.getAttribute('title') || '';
      var desk = (b.getAttribute('data-desk') || '').trim();
      var kat  = cariKategori(b);
      document.getElementById('popup-apaitu-nama').textContent = nama;
      document.getElementById('popup-apaitu-isi').textContent = desk || 'Belum ada penjelasan untuk jenis sampah ini.';
      var k = document.getElementById('popup-apaitu-kat');
      if (kat) { k.textContent = kat; k.style.display = 'inline-block'; } else { k.style.display = 'none'; }
      p.scrollTop = 0; lt.style.display = 'block'; p.style.display = 'block';
      return;
    }
    if (e.target === lt || (e.target.closest && e.target.closest('#popup-apaitu-tutup'))) tutup();
  }, true);

  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') tutup(); });
})();
</script>

EOT;

$pos = strrpos($s, '@endsection');
if ($pos === false) { exit("@endsection tidak ditemukan, tidak ada yang diubah.\n"); }
$s2 = substr($s, 0, $pos) . $blok . substr($s, $pos);

copy($f, $f . '.bak-apaitu2');
file_put_contents($f, $s2);
echo "Selesai. Popup baru terpasang. Cadangan: sampah.blade.php.bak-apaitu2\n";