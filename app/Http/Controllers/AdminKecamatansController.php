<?php namespace App\Http\Controllers;

use Session;
use Request;
use DB;
use CRUDBooster;
use crocodicstudio\crudbooster\controllers\CBController;

class AdminKecamatansController extends CBController {

    public function cbInit() {

        # START CONFIGURATION DO NOT REMOVE THIS LINE
        $this->title_field         = "nama_kecamatan";
        $this->limit               = "20";
        $this->orderby             = "nama_kecamatan,asc";
        $this->global_privilege    = false;
        $this->button_table_action = true;
        $this->button_bulk_action  = true;
        $this->button_action_style = "button_icon";
        $this->button_add          = true;
        $this->button_edit         = true;
        $this->button_delete       = true;
        $this->button_detail       = true;
        $this->button_show         = true;
        $this->button_filter       = true;
        $this->button_import       = false;
        $this->button_export       = false;
        $this->table               = "kecamatans";
        # END CONFIGURATION DO NOT REMOVE THIS LINE

        # START COLUMNS DO NOT REMOVE THIS LINE
        $this->col = [];
        $this->col[] = ["label" => "No", "callback_php" => '($row->index_number = (++$GLOBALS["index_number"]))'];
        $this->col[] = ["label" => "Nama Kecamatan", "name" => "nama_kecamatan"];
        $this->col[] = ["label" => "Dibuat Pada", "name" => "created_at", "callback" => function($row) {
            return $row->created_at ? date('d/m/Y H:i', strtotime($row->created_at)) : '-';
        }];
        # END COLUMNS DO NOT REMOVE THIS LINE

        # START FORM DO NOT REMOVE THIS LINE
        $this->form = [];
        $this->form[] = [
            'label'       => 'Nama Kecamatan',
            'name'        => 'nama_kecamatan',
            'type'        => 'text',
            'validation'  => 'required|string|min:2|max:100',
            'width'       => 'col-sm-8',
            'placeholder' => 'Contoh: Kembangan, Palmerah, dsb.'
        ];
        # END FORM DO NOT REMOVE THIS LINE

    }

    public function hook_query_index(&$query) {
        $GLOBALS['index_number'] = 0;
    }

}
