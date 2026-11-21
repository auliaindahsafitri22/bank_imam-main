@extends('crudbooster::admin_template')
@section('content')
<p><a href="{{ CRUDBooster::mainpath('kirim') }}" class="btn btn-success btn-lg"><i class="fa fa-paper-plane"></i> Kirim pengumuman baru</a></p>
<div class="box box-default"><div class="box-header"><h3 class="box-title">Pengumuman terkirim</h3></div>
<div class="box-body table-responsive"><table class="table table-striped table-bordered">
<thead><tr><th>Tanggal</th><th>Judul</th><th>Pengirim</th><th class="text-center">BSU tujuan</th><th class="text-center">Sudah dibaca</th><th></th></tr></thead><tbody>
@forelse($rows as $r)
<tr style="{{ $r->dihapus_at ? 'color:#888;background:#f7f7f7' : '' }}"><td>{{ date('d/m/Y H:i', strtotime($r->created_at)) }}</td><td>@if($r->dihapus_at)<s>{{ $r->judul }}</s> <span class="label label-danger" title="Dihapus {{ date('d/m/Y H:i', strtotime($r->dihapus_at)) }}{{ $r->penghapus ? ' oleh '.$r->penghapus : '' }}">DIHAPUS {{ date('d/m/Y', strtotime($r->dihapus_at)) }}</span>@else{{ $r->judul }}@endif @if($r->lampiran)<i class="fa fa-paperclip text-muted" title="{{ $r->lampiran }} lampiran"></i>@endif</td><td>{{ $r->pengirim }}</td>
<td class="text-center">{{ $r->tujuan }}</td><td class="text-center">{{ $r->dibaca }} / {{ $r->tujuan }}</td>
<td style="white-space:nowrap"><a class="btn btn-xs btn-info" href="{{ CRUDBooster::mainpath('rincian/'.$r->id) }}">Rincian</a>
 @if($r->dihapus_at)
  <form method="post" action="{{ CRUDBooster::mainpath('pulihkan') }}" style="display:inline">{{ csrf_field() }}<input type="hidden" name="id" value="{{ $r->id }}"><button class="btn btn-xs btn-success"><i class="fa fa-undo"></i> Pulihkan</button></form>
 @else
  <form method="post" action="{{ CRUDBooster::mainpath('hapus') }}" style="display:inline" onsubmit="return confirm('Hapus pengumuman ini? BSU akan melihatnya bertanda DIHAPUS tanpa isi. Bisa dipulihkan.')">{{ csrf_field() }}<input type="hidden" name="id" value="{{ $r->id }}"><button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Hapus</button></form>
 @endif</td></tr>
@empty<tr><td colspan="6" class="text-center text-muted">Belum ada pengumuman.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div>
@endsection
