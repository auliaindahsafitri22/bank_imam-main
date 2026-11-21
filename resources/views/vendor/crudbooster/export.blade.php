@php
    $isPdf = Request::input('fileformat') == 'pdf';
    $judulFile = trim((string) Request::input('filename'));
    // "Report - 20 Nov 2026" adalah nama bawaan file, bukan judul laporan.
    $tampilJudul = $judulFile !== '' && !preg_match('/^report\s*-/i', $judulFile);
    $wilayah = strtoupper(config('app.wilayah', 'JAKARTA BARAT'));
    $adalahBsu = (int) CRUDBooster::myPrivilegeId() === 4;
@endphp
@if($isPdf)
    <h2 style="text-align: center; margin-bottom: 2px;">ECOBUANA</h2>
    <h3 style="text-align: center; margin-top: 0;">{{ $wilayah }}</h3>
    <hr style="border: 1px solid #555;">
    <h3>Tanggal: {{ date('d-m-Y') }}</h3>
    @if($adalahBsu)
        <h3>Nama Bank Sampah Unit: {{ CRUDBooster::myName() }}</h3>
    @else
        <h3>Dicetak oleh: {{ CRUDBooster::myName() }}</h3>
    @endif
    @if($tampilJudul)
        <h4>{{ $judulFile }}</h4>
    @endif
@endif
<table border='1' width='100%' cellpadding='3' cellspacing="0" style='border-collapse: collapse;font-size:12px'>
    <thead>
    <tr>
        <?php
        foreach ($columns as $col) {

            if (Request::get('columns')) {
                if (! in_array($col['name'], Request::get('columns'))) {
                    continue;
                }
            }
            $colname = $col['label'];
            echo "<th style='background:#eeeeee'>$colname</th>";
        }
        ?>
    </tr>
    </thead>
    <tbody>
    @if(count($result)==0)
        <tr class='warning'>
            <td colspan='{{count($columns)+1}}' align="center">No Data Avaliable</td>
        </tr>
    @else
        @foreach($result as $row)
            <tr>
                <?php
                foreach ($columns as $col) {

                    if (Request::get('columns')) {
                        if (! in_array($col['name'], Request::get('columns'))) {
                            continue;
                        }
                    }

                    $value = @$row->{$col['field']};
                    $title = @$row->{$title_field};

                    if (@$col['image']) {
                        if ($value == '') {
                            $value = "http://placehold.it/50x50&text=NO+IMAGE";
                        }
                        $pic = (strpos($value, 'http://') !== FALSE) ? $value : asset($value);
                        $pic_small = $pic;
                        if (Request::input('fileformat') == 'pdf') {
                            echo "<td><a data-lightbox='roadtrip' rel='group_{{$table}}' title='$col[label]: $title' href='".$pic."'><img class='img-circle' width='40px' height='40px' src='".$pic_small."'/></a></td>";
                        } else {
                            echo "<td>$pic</td>";
                        }
                    } elseif (@$col['download']) {
                        $url = (strpos($value, 'http://') !== FALSE) ? $value : asset($value);
                        echo "<td><a class='btn btn-sm btn-primary' href='$url' target='_blank' title='Download File'>Download</a></td>";
                    } else {

                        //limit character
                        if ($col['str_limit']) {
                            $value = trim(strip_tags($value));
                            $value = str_limit($value, $col['str_limit']);
                        }

                        if ($col['nl2br']) {
                            $value = nl2br($value);
                        }

                        if (Request::input('fileformat') == 'pdf') {
                            if (! empty($col['callback_php'])) {

                                foreach ($row as $k => $v) {
                                    $col['callback_php'] = str_replace("[".$k."]", $v, $col['callback_php']);
                                }
                                @eval("\$value = ".$col['callback_php'].";");
                            }

                            //New method for callback
                            if (isset($col['callback'])) {
                                $value = call_user_func($col['callback'], $row);
                            }
                        }

                        echo "<td>".$value."</td>";
                    }
                }
                ?>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>
@if($isPdf)
    <br><br>
    <table width="100%" style="font-size:12px; border-collapse: collapse;">
        <tr>
            <td width="50%" style="font-weight:bold;">DITERIMA OLEH</td>
            <td width="50%" style="font-weight:bold; text-align:right;">DISERAHKAN OLEH</td>
        </tr>
        <tr><td colspan="2" style="height:40px;"></td></tr>
        <tr>
            <td>..................................</td>
            <td style="text-align:right;">..................................</td>
        </tr>
    </table>
@endif
<script type="text/php">
    if ( isset($pdf) ) {
        $font = Font_Metrics::get_font("helvetica", "bold");
        $pdf->page_text(36, 18, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 6, array(0,0,0));
    }

</script>