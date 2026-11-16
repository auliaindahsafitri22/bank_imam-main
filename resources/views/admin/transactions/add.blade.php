@extends('crudbooster::admin_template')

@section('content')

    <div>
        {{-- Tombol Kembali ke Daftar --}}
        <p>
            <a title="Kembali ke halaman Tabungan Nasabah" href="{{ CRUDBooster::mainpath() }}" class="text-primary" style="font-weight: 600;">
                <i class="fa fa-chevron-circle-left"></i> &nbsp; Kembali ke halaman Tabungan Nasabah
            </a>
        </p>

        {{-- Alert Notifikasi jika ada flash message --}}
        @if(session('message'))
            <div class="alert alert-{{ session('message_type') ?? 'info' }} alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <i class="fa fa-info-circle"></i> {!! session('message') !!}
            </div>
        @endif

        <div class="panel panel-default" style="box-shadow: 0 2px 4px rgba(0,0,0,0.08); border-radius: 4px; border: 1px solid #d2d6de;">
            <div class="panel-heading" style="background: #3c8dbc; color: #ffffff; border-radius: 3px 3px 0 0; padding: 12px 15px;">
                <strong style="font-size: 16px;">
                    <i class="fa fa-money"></i> {{ $page_title }}
                </strong>
            </div>

            <form class="form-horizontal" method="post" id="form-tabungan-multiple" action="{{ CRUDBooster::mainpath('add-save') }}">
                @csrf
                <input type="hidden" name="return_url" value="{{ g('return_url') }}"/>
                <input type="hidden" name="ref_mainpath" value="{{ CRUDBooster::mainpath() }}"/>

                <div class="panel-body" style="padding: 20px;">

                    {{-- SECTION 1: MASTER NASABAH & PERIODE --}}
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 18px 20px 8px 20px; margin-bottom: 22px;">
                        <h4 style="margin-top: 0; margin-bottom: 15px; font-weight: 700; color: #1e293b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fa fa-user text-primary" style="margin-right: 6px;"></i> Informasi Nasabah & Periode
                        </h4>
                        
                        <div class="row">
                            {{-- Pilih Nasabah --}}
                            <div class="col-md-6">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label class="control-label col-sm-4" style="text-align: left; font-weight: 600; color: #334155;">
                                        Nama User <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-sm-8">
                                        <select name="user_id" id="user_id" class="form-control select2" required style="width: 100%;">
                                            <option value="" disabled {{ old('user_id') ? '' : 'selected' }}>** Silahkan pilih Nama User</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->username }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="help-block text-muted" style="margin-bottom: 0;">Pilih nasabah yang menyetorkan sampah</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Tanggal / Periode --}}
                            <div class="col-md-6">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label class="control-label col-sm-4" style="text-align: left; font-weight: 600; color: #334155;">
                                        Tanggal Transaksi <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <span class="input-group-addon open-datetimepicker" style="cursor: pointer; background: #f1f5f9; border-color: #d2d6de;" title="Buka Kalender">
                                                <i class="fa fa-calendar text-primary"></i>
                                            </span>
                                            <input type="text" name="periode" id="periode" class="form-control input_date" required readonly
                                                   value="{{ old('periode', date('Y-m-d')) }}" placeholder="Pilih Tanggal Transaksi" autocomplete="off"
                                                   style="background: #ffffff; cursor: pointer; font-weight: 600;">
                                        </div>
                                        <small class="help-block text-muted" style="margin-bottom: 0;">Pilih tanggal penimbangan / penyetoran sampah nasabah</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECTION 2: MULTIPLE JENIS SAMPAH (REPEATER) --}}
                    <div class="panel panel-default" style="border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 20px;">
                        <div class="panel-heading" style="background: #f1f5f9; border-bottom: 1px solid #cbd5e1; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 14px; color: #0f172a;">
                                    <i class="fa fa-cubes text-primary" style="margin-right: 6px;"></i> Input Rincian Sampah
                                </strong>
                                <span class="badge bg-blue" id="badge-item-count" style="margin-left: 8px;">1 Item</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-success" id="btn-add-row" style="font-weight: 600; border-radius: 4px;">
                                <i class="fa fa-plus-circle"></i> Tambah Jenis Sampah
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="table-sampah-items" style="margin-bottom: 0; vertical-align: middle;">
                                <thead>
                                    <tr style="background: #f8fafc; color: #334155; font-size: 12px; text-transform: uppercase;">
                                        <th style="width: 45px; text-align: center; vertical-align: middle;">#</th>
                                        <th style="min-width: 280px; vertical-align: middle;">Jenis Sampah <span class="text-danger">*</span></th>
                                        <th style="width: 140px; vertical-align: middle;">Total Berat (kg) <span class="text-danger">*</span></th>
                                        <th style="width: 130px; text-align: right; vertical-align: middle;">Harga / kg</th>
                                        <th style="width: 110px; text-align: right; vertical-align: middle;">Poin / kg</th>
                                        <th style="width: 170px; text-align: right; vertical-align: middle;">Total Pendapatan</th>
                                        <th style="width: 130px; text-align: right; vertical-align: middle;">Poin Didapat</th>
                                        <th style="width: 60px; text-align: center; vertical-align: middle;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="items-container">
                                    {{-- Baris dibuat otomatis oleh javascript --}}
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f8fafc; font-weight: bold; border-top: 2px solid #cbd5e1;">
                                        <td colspan="2" style="text-align: right; vertical-align: middle; font-size: 13px; color: #1e293b;">
                                            TOTAL KESELURUHAN:
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <span id="grand-total-weight-display" class="text-primary" style="font-size: 15px; font-weight: 700;">0.00</span>
                                            <span style="font-size: 11px; color: #64748b;">kg</span>
                                        </td>
                                        <td colspan="2" style="text-align: right; vertical-align: middle; color: #64748b; font-size: 12px;">
                                            <span id="grand-items-count-text">1</span> item disetor
                                        </td>
                                        <td style="text-align: right; vertical-align: middle;">
                                            <span style="font-size: 15px; font-weight: 700; color: #16a34a;">
                                                Rp <span id="grand-total-income-display">0</span>
                                            </span>
                                        </td>
                                        <td style="text-align: right; vertical-align: middle;">
                                            <span style="font-size: 15px; font-weight: 700; color: #ea580c;">
                                                <span id="grand-total-points-display">0</span>
                                            </span>
                                            <span style="font-size: 11px; color: #64748b;">Poin</span>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <button type="button" class="btn btn-xs btn-default" id="btn-add-row-bottom" title="Tambah Baris Baru">
                                                <i class="fa fa-plus text-success"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- SECTION 3: REKAPITULASI KARTU STATISTIK (RINGKASAN CEPAT) --}}
                    <div class="row" style="margin-bottom: 10px;">
                        <div class="col-md-4">
                            <div class="info-box bg-aqua" style="border-radius: 6px; min-height: 70px; margin-bottom: 10px;">
                                <span class="info-box-icon" style="height: 70px; line-height: 70px; border-radius: 6px 0 0 6px;"><i class="fa fa-balance-scale"></i></span>
                                <div class="info-box-content" style="padding: 10px;">
                                    <span class="info-box-text" style="font-size: 12px;">Total Berat Sampah</span>
                                    <span class="info-box-number" style="font-size: 20px;"><span id="stat-total-weight">0.00</span> <small style="color: #fff;">kg</small></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-green" style="border-radius: 6px; min-height: 70px; margin-bottom: 10px;">
                                <span class="info-box-icon" style="height: 70px; line-height: 70px; border-radius: 6px 0 0 6px;"><i class="fa fa-money"></i></span>
                                <div class="info-box-content" style="padding: 10px;">
                                    <span class="info-box-text" style="font-size: 12px;">Total Saldo Didapat</span>
                                    <span class="info-box-number" style="font-size: 20px;">Rp <span id="stat-total-income">0</span></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-yellow" style="border-radius: 6px; min-height: 70px; margin-bottom: 10px;">
                                <span class="info-box-icon" style="height: 70px; line-height: 70px; border-radius: 6px 0 0 6px;"><i class="fa fa-star"></i></span>
                                <div class="info-box-content" style="padding: 10px;">
                                    <span class="info-box-text" style="font-size: 12px;">Total Poin Didapat</span>
                                    <span class="info-box-number" style="font-size: 20px;"><span id="stat-total-points">0</span> <small style="color: #fff;">Poin</small></span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- /.panel-body --}}

                <div class="panel-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 15px 20px;">
                    <div class="row">
                        <div class="col-sm-6 text-left">
                            <a href="{{ CRUDBooster::mainpath() }}" class="btn btn-default" style="font-weight: 600;">
                                <i class="fa fa-chevron-circle-left"></i> Kembali
                            </a>
                        </div>
                        <div class="col-sm-6 text-right">
                            <button type="submit" name="submit" value="Simpan & Tambah Lagi" class="btn btn-success" style="font-weight: 600; margin-right: 5px;">
                                <i class="fa fa-save"></i> Simpan & Tambah Lagi
                            </button>
                            <button type="submit" name="submit" value="Simpan" class="btn btn-primary" style="font-weight: 600;">
                                <i class="fa fa-check-circle"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>{{-- /.panel-footer --}}

            </form>
        </div>
    </div>

    {{-- TEMPLATE SELECT OPTION DATA SAMPAH --}}
    <div id="sampah-options-template" style="display: none;">
        <option value="" disabled selected>** Silahkan pilih Jenis Sampah</option>
        @foreach($sampahList as $sampah)
            <option value="{{ $sampah->id }}" 
                    data-price="{{ $sampah->price_per_kg }}" 
                    data-poin="{{ $sampah->poin_per_kg }}">
                {{ $sampah->name }} (Rp {{ number_format($sampah->price_per_kg, 0, ',', '.') }}/kg | {{ number_format($sampah->poin_per_kg, 0, ',', '.') }} Poin/kg)
            </option>
        @endforeach
    </div>

@endsection

@push('bottom')
<link rel="stylesheet" href="{{ asset('vendor/crudbooster/assets/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/crudbooster/assets/adminlte/plugins/datepicker/datepicker3.css') }}">
<script src="{{ asset('vendor/crudbooster/assets/select2/dist/js/select2.full.min.js') }}"></script>
<script src="{{ asset('vendor/crudbooster/assets/adminlte/plugins/datepicker/bootstrap-datepicker.js') }}"></script>

<style>
    .datepicker.dropdown-menu {
        z-index: 9999 !important;
    }
    .select2-container .select2-selection--single {
        height: 34px !important;
        border: 1px solid #d2d6de !important;
        border-radius: 4px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px !important;
        color: #333 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px !important;
    }
    .item-row:hover {
        background-color: #f1f5f9 !important;
    }
    .input-readonly-custom {
        background-color: #f8fafc !important;
        cursor: not-allowed;
        font-weight: 600;
    }
</style>

<script>
$(document).ready(function() {
    // 1. Inisialisasi Select2 pada Nama User
    $('#user_id').select2({
        placeholder: "** Silahkan pilih Nama User",
        allowClear: false,
        width: '100%'
    });

    // 2. Inisialisasi Datepicker Widget pada Tanggal Transaksi
    $('#periode').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        todayBtn: "linked",
        orientation: "bottom auto"
    });

    $('.open-datetimepicker').on('click', function() {
        $('#periode').datepicker('show');
    });

    let rowIndexCounter = 0;
    const sampahOptionsHtml = $('#sampah-options-template').html();

    function showAlert(title, text, icon = 'warning') {
        if (typeof swal === 'function') {
            swal({
                title: title,
                text: text,
                icon: icon,
                buttons: { confirm: { className: 'btn btn-primary' } }
            });
        } else {
            alert(title + "\n" + text);
        }
    }

    // Helper format angka ribuan (e.g. 15.000)
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Helper parse float aman
    function parseSafeFloat(val) {
        if (!val) return 0;
        let str = String(val).replace(',', '.');
        let num = parseFloat(str);
        return isNaN(num) ? 0 : num;
    }

    // 2. Fungsi Buat Baris Baru
    function createRow(initialData = null) {
        rowIndexCounter++;
        const currentIndex = rowIndexCounter;

        const rowHtml = `
            <tr class="item-row" id="row-${currentIndex}" data-index="${currentIndex}">
                <td style="text-align: center; vertical-align: middle; font-weight: bold; color: #64748b;" class="row-number">
                    1
                </td>
                <td style="vertical-align: middle;">
                    <select name="items[${currentIndex}][sampah_id]" class="form-control select2 select-sampah" required style="width: 100%;">
                        ${sampahOptionsHtml}
                    </select>
                </td>
                <td style="vertical-align: middle;">
                    <input type="number" step="0.01" min="0.01" name="items[${currentIndex}][total_weight]" 
                           class="form-control input-weight" placeholder="0.00" required
                           style="font-weight: 600; text-align: right;" value="${initialData && initialData.weight ? initialData.weight : ''}">
                </td>
                <td style="text-align: right; vertical-align: middle; font-weight: 600; color: #475569;" class="cell-price">
                    -
                </td>
                <td style="text-align: right; vertical-align: middle; font-weight: 600; color: #475569;" class="cell-poin">
                    -
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <input type="text" class="form-control input-readonly-custom text-right display-income" 
                           readonly value="Rp 0" style="color: #16a34a; font-weight: 700;">
                    <input type="hidden" name="items[${currentIndex}][total_income]" class="val-income" value="0">
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <input type="text" class="form-control input-readonly-custom text-right display-points" 
                           readonly value="0" style="color: #ea580c; font-weight: 700;">
                    <input type="hidden" name="items[${currentIndex}][point_received]" class="val-points" value="0">
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-row" title="Hapus item ini" style="border-radius: 4px;">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        const $newRow = $(rowHtml);
        $('#items-container').append($newRow);

        // Inisialisasi Select2 pada dropdown sampah baris baru
        $newRow.find('.select-sampah').select2({
            placeholder: "** Silahkan pilih Jenis Sampah",
            width: '100%'
        });

        // Set initial data jika ada
        if (initialData && initialData.sampah_id) {
            $newRow.find('.select-sampah').val(initialData.sampah_id).trigger('change');
        }

        updateRowNumbers();
        recalculateRow($newRow);
        updateGrandTotals();
    }

    // 3. Fungsi Perbarui Nomor Urut Baris
    function updateRowNumbers() {
        let count = 0;
        $('#items-container tr.item-row').each(function(idx) {
            count++;
            $(this).find('.row-number').text(idx + 1);
        });
        $('#badge-item-count').text(count + ' Item');
        $('#grand-items-count-text').text(count);
    }

    // 4. Hitung Ulang Per Baris
    function recalculateRow($row) {
        const $select = $row.find('.select-sampah');
        const $selectedOption = $select.find('option:selected');

        const pricePerKg = parseSafeFloat($selectedOption.data('price'));
        const poinPerKg  = parseSafeFloat($selectedOption.data('poin'));
        const weight     = parseSafeFloat($row.find('.input-weight').val());

        // Update tampilan snapshot harga dan poin per kg
        if ($selectedOption.val()) {
            $row.find('.cell-price').text('Rp ' + formatNumber(pricePerKg));
            $row.find('.cell-poin').text(formatNumber(poinPerKg) + ' Poin');
        } else {
            $row.find('.cell-price').text('-');
            $row.find('.cell-poin').text('-');
        }

        // Perhitungan total pendapatan & poin didapat (dibulatkan ke integer)
        const rowIncome = Math.round(weight * pricePerKg);
        const rowPoints = Math.round(weight * poinPerKg);

        $row.find('.display-income').val('Rp ' + formatNumber(rowIncome));
        $row.find('.val-income').val(rowIncome);

        $row.find('.display-points').val(formatNumber(rowPoints));
        $row.find('.val-points').val(rowPoints);
    }

    // 5. Hitung Ulang Grand Total Keseluruhan
    function updateGrandTotals() {
        let totalWeight = 0;
        let totalIncome = 0;
        let totalPoints = 0;

        $('#items-container tr.item-row').each(function() {
            const $row = $(this);
            const weight = parseSafeFloat($row.find('.input-weight').val());
            const income = parseSafeFloat($row.find('.val-income').val());
            const points = parseSafeFloat($row.find('.val-points').val());

            totalWeight += weight;
            totalIncome += income;
            totalPoints += points;
        });

        const formattedWeight = totalWeight.toFixed(2);
        const formattedIncome = formatNumber(totalIncome);
        const formattedPoints = formatNumber(totalPoints);

        // Update tfoot
        $('#grand-total-weight-display').text(formattedWeight);
        $('#grand-total-income-display').text(formattedIncome);
        $('#grand-total-points-display').text(formattedPoints);

        // Update statistik card
        $('#stat-total-weight').text(formattedWeight);
        $('#stat-total-income').text(formattedIncome);
        $('#stat-total-points').text(formattedPoints);
    }

    // Event Delegation: Ketika Sampah Berubah
    $(document).on('change', '.select-sampah', function() {
        const $row = $(this).closest('tr');
        recalculateRow($row);
        updateGrandTotals();
    });

    // Event Delegation: Ketika Berat Diketik / Diubah
    $(document).on('input change', '.input-weight', function() {
        const $row = $(this).closest('tr');
        recalculateRow($row);
        updateGrandTotals();
    });

    // Event Delegation: Hapus Baris
    $(document).on('click', '.btn-remove-row', function() {
        const rowCount = $('#items-container tr.item-row').length;
        if (rowCount <= 1) {
            showAlert("Perhatian", "Minimal harus ada 1 jenis sampah dalam transaksi!", "warning");
            return;
        }

        const $row = $(this).closest('tr');
        // Hancurkan select2 sebelum remove untuk cegah memory leak
        $row.find('.select-sampah').select2('destroy');
        $row.remove();

        updateRowNumbers();
        updateGrandTotals();
    });

    // Tombol Tambah Baris
    $('#btn-add-row, #btn-add-row-bottom').on('click', function(e) {
        e.preventDefault();
        createRow();
    });

    // Validasi Form Sebelum Submit
    $('#form-tabungan-multiple').on('submit', function(e) {
        const userId = $('#user_id').val();
        if (!userId) {
            e.preventDefault();
            showAlert("Nasabah Belum Dipilih", "Silahkan pilih Nama User terlebih dahulu!", "warning");
            $('#user_id').select2('open');
            return false;
        }

        let hasValidItem = false;
        let emptyWeightFound = false;

        $('#items-container tr.item-row').each(function() {
            const sampahId = $(this).find('.select-sampah').val();
            const weight = parseSafeFloat($(this).find('.input-weight').val());

            if (sampahId && weight > 0) {
                hasValidItem = true;
            } else if (sampahId && weight <= 0) {
                emptyWeightFound = true;
            }
        });

        if (!hasValidItem) {
            e.preventDefault();
            showAlert("Rincian Sampah Kosong", "Harap pilih minimal 1 jenis sampah dan masukkan total berat yang valid (lebih dari 0)!", "warning");
            return false;
        }

        if (emptyWeightFound) {
            e.preventDefault();
            showAlert("Berat Belum Diisi", "Terdapat jenis sampah yang belum diisi beratnya atau bernilai 0. Silahkan periksa kembali!", "warning");
            return false;
        }
    });

    // Inisialisasi Baris Pertama (atau pulihkan data old() jika redirect back validation)
    @if(old('items') && is_array(old('items')))
        @foreach(old('items') as $oldItem)
            createRow({
                sampah_id: "{{ $oldItem['sampah_id'] ?? '' }}",
                weight: "{{ $oldItem['total_weight'] ?? '' }}"
            });
        @endforeach
    @else
        createRow();
    @endif
});
</script>
@endpush
