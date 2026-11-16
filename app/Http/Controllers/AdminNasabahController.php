<?php namespace App\Http\Controllers;

	use Session;
	use Request;
	use DB;
	use CRUDBooster;

	class AdminNasabahController extends \crocodicstudio\crudbooster\controllers\CBController {

	    public function cbInit() {

			# START CONFIGURATION DO NOT REMOVE THIS LINE
			$this->title_field = "username";
			$this->limit = "20";
			$this->orderby = "updated_at,desc";
			$this->global_privilege = false;
			$this->button_table_action = true;
			$this->button_bulk_action = true;
			$this->button_action_style = "button_icon";
			$this->button_add = true;
			$this->button_edit = true;
			$this->button_delete = true;
			$this->button_detail = true;
			$this->button_show = true;
			$this->button_filter = true;
			$this->button_import = false;
			$this->button_export = true;
			$this->table = "users";
			# END CONFIGURATION DO NOT REMOVE THIS LINE

			# START COLUMNS DO NOT REMOVE THIS LINE
			$this->col = [];
			$this->col[] = ["label" => "No", "callback_php" => '($row->index_number = (++$GLOBALS["index_number"]))'];
			$this->col[] = ["label"=>"Username","name"=>"username"];
			$this->col[] = ["label"=>"Nama BSU","name"=>"cms_user_id","join"=>"cms_users,name"];
			$this->col[] = ["label"=>"Email","name"=>"email"];
			$this->col[] = ["label"=>"Alamat","name"=>"address"];
			$this->col[] = ["label"=>"No. Hp","name"=>"phone_number"];
			$this->col[] = ["label"=>"Foto","name"=>"picture","image"=>true];
			$this->col[] = ["label" => "Total Transaksi (Rp)", "callback_php" => '"Rp ".number_format(\App\Http\Controllers\AdminNasabahController::nasabahStat($row->id, "rp"), 0, ",", ".")'];
			$this->col[] = ["label" => "Total Berat (kg)", "callback_php" => 'number_format(\App\Http\Controllers\AdminNasabahController::nasabahStat($row->id, "kg"), 2, ",", ".")'];
			$this->col[] = ["label" => "Transaksi Terakhir", "callback_php" => '\App\Http\Controllers\AdminNasabahController::nasabahStat($row->id, "terakhir")'];
			$this->col[] = ["label" => "Aktivitas", "callback_php" => '\App\Http\Controllers\AdminNasabahController::nasabahStat($row->id, "aktivitas")'];
			# END COLUMNS DO NOT REMOVE THIS LINE

			# START FORM DO NOT REMOVE THIS LINE
			$this->form = [];
			$this->form[] = ['label'=>'Username','name'=>'username','type'=>'text','validation'=>'required|min:1|max:255','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Email','name'=>'email','type'=>'email','validation'=>'required|min:1|max:255|email|unique:users','width'=>'col-sm-10','placeholder'=>'Please enter a valid email address'];
			$this->form[] = ['label'=>'Password','name'=>'password','type'=>'password','validation'=>'min:3|max:32','width'=>'col-sm-10','help'=>'Minimum 5 characters. Please leave empty if you did not change the password.'];
			$this->form[] = ['label'=>'Alamat','name'=>'address','type'=>'text','validation'=>'required|min:1|max:255','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'No. HP','name'=>'phone_number','type'=>'number','validation'=>'required|numeric','width'=>'col-sm-10','placeholder'=>'You can only enter the number only'];
			$this->form[] = ['label'=>'Foto','name'=>'picture','type'=>'upload','validation'=>'required|image|max:3000','width'=>'col-sm-10','help'=>'File types support : JPG, JPEG, PNG, GIF, BMP'];
			# END FORM DO NOT REMOVE THIS LINE

	        $this->sub_module = array();
	        $this->addaction = array();
	        $this->button_selected = array();
	        $this->alert        = array();
	        $this->index_button = array();
	        $this->table_row_color = array();
	        $this->index_statistic = array();
	        $this->script_js = NULL;
	        $this->pre_index_html = null;
	        $this->post_index_html = null;
	        $this->load_js = array();
	        $this->style_css = NULL;
	        $this->load_css[] =  asset('ecobuana/bootstrap/css/admin.css');
	    }

	    public function actionButtonSelected($id_selected,$button_name) {
	    }

	    public static function nasabahStat($id, $field)
	    {
	        static $stats = null;
	        if ($stats === null) {
	            $q = DB::table('transactions')
	                ->whereNotNull('user_id')
	                ->select('user_id',
	                    DB::raw('SUM(total_income) AS rp'),
	                    DB::raw('SUM(total_weight) AS kg'),
	                    DB::raw('MAX(created_at) AS terakhir'))
	                ->groupBy('user_id');

	            // pengelola hanya menghitung transaksi di BSU-nya sendiri
	            $me = CRUDBooster::myId();
	            if ($me != 1 && $me != 10) {
	                $q->where('admin_id', $me);
	            }
	            $stats = $q->get()->keyBy('user_id');
	        }

	        $s = isset($stats[$id]) ? $stats[$id] : null;

	        switch ($field) {
	            case 'rp':
	                return $s ? (float) $s->rp : 0;
	            case 'kg':
	                return $s ? (float) $s->kg : 0;
	            case 'terakhir':
	                return ($s && $s->terakhir) ? date('d-m-Y', strtotime($s->terakhir)) : '-';
	            case 'aktivitas':
	                if (!$s || !$s->terakhir) {
	                    return '<span class="label label-default">Belum pernah</span>';
	                }
	                $hari = (time() - strtotime($s->terakhir)) / 86400;
	                return $hari <= 30
	                    ? '<span class="label label-success">Aktif</span>'
	                    : '<span class="label label-danger">Tidak aktif</span>';
	        }
	        return '';
	    }

	    public function hook_query_index(&$query) {
	        $currentUserId = CRUDBooster::myId();

	        $GLOBALS['index_number'] = 0;

			if ($currentUserId == 1) {

			}elseif($currentUserId == 10) {

			}
			else {
				$query->where('cms_user_id', $currentUserId);
			}
	    }

	    public function hook_row_index($column_index,&$column_value) {
	    }

	    public function hook_before_add(&$postdata) {
	    }

	    public function hook_after_add($id) {
	    }

	    public function hook_before_edit(&$postdata,$id) {
	    }

	    public function hook_after_edit($id) {
	    }

	    public function hook_before_delete($id) {
	    }

	    public function hook_after_delete($id) {
	    }

	}