@php
    $canFilterKelurahan = \CRUDBooster::isSuperadmin() || in_array(\CRUDBooster::myPrivilegeId(), [1, 2]) || in_array(\CRUDBooster::myId(), [1, 10]);
@endphp

@if($canFilterKelurahan)
@php
    $kelurahans = \DB::table('kelurahans')->whereNull('deleted_at')->orderBy('nama_kelurahan', 'asc')->get();
    $filterColumn = Request::get('filter_column');
    $selectedKelurahan = $filterColumn['kelurahans.nama_kelurahan']['value'] ?? '';
    $currentUrl = Request::url();
    $allParams = Request::all();
@endphp

<div class="box box-default" style="margin-bottom: 15px; border-radius: 4px; border-top: 3px solid #00a65a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <div class="box-body" style="padding: 10px 15px;">
        <form method="GET" action="{{ $currentUrl }}" class="form-inline" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
            <input type="hidden" name="filter_column[kelurahans.nama_kelurahan][type]" value="=">
            
            {{-- Preserve other query params like sorting or other filters --}}
            @foreach($allParams as $paramKey => $paramVal)
                @if($paramKey === 'filter_column')
                    @if(is_array($paramVal))
                        @foreach($paramVal as $colKey => $colDef)
                            @if($colKey !== 'kelurahans.nama_kelurahan' && is_array($colDef))
                                @foreach($colDef as $k => $v)
                                    @if(is_array($v))
                                        @foreach($v as $subV)
                                            <input type="hidden" name="filter_column[{{ $colKey }}][{{ $k }}][]" value="{{ $subV }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="filter_column[{{ $colKey }}][{{ $k }}]" value="{{ $v }}">
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    @endif
                @elseif($paramKey !== 'page' && !is_array($paramVal))
                    <input type="hidden" name="{{ $paramKey }}" value="{{ $paramVal }}">
                @endif
            @endforeach

            <div class="form-group" style="margin-bottom: 0;">
                <label for="filter_kelurahan_select" style="margin-right: 8px; font-weight: 600; color: #333;">
                    <i class="fa fa-map-marker text-success" style="font-size: 16px; margin-right: 4px;"></i> Filter Kelurahan:
                </label>
                <select name="filter_column[kelurahans.nama_kelurahan][value]" id="filter_kelurahan_select" class="form-control input-sm" style="min-width: 200px;" onchange="this.form.submit()">
                    <option value="">-- Semua Kelurahan --</option>
                    @foreach($kelurahans as $kel)
                        <option value="{{ $kel->nama_kelurahan }}" {{ $selectedKelurahan == $kel->nama_kelurahan ? 'selected' : '' }}>
                            {{ $kel->nama_kelurahan }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if(!empty($selectedKelurahan))
                <span class="label label-success" style="font-size: 12px; padding: 5px 8px;">
                    <i class="fa fa-check-circle"></i> Wilayah: {{ $selectedKelurahan }}
                </span>
                @php
                    $resetParams = $allParams;
                    if (isset($resetParams['filter_column']['kelurahans.nama_kelurahan'])) {
                        unset($resetParams['filter_column']['kelurahans.nama_kelurahan']);
                        if (empty($resetParams['filter_column'])) {
                            unset($resetParams['filter_column']);
                        }
                    }
                    $resetUrl = $currentUrl . (count($resetParams) ? '?' . http_build_query($resetParams) : '');
                @endphp
                <a href="{{ $resetUrl }}" class="btn btn-sm btn-default" style="margin-left: 5px;">
                    <i class="fa fa-times text-danger"></i> Reset Filter
                </a>
            @endif
        </form>
    </div>
</div>
@endif

