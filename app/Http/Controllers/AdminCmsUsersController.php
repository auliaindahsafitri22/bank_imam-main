<?php namespace App\Http\Controllers;

use Session;
use Request;
use DB;
use CRUDBooster;
use crocodicstudio\crudbooster\controllers\CBController;

class AdminCmsUsersController extends CBController {


	public function cbInit() {

		# START CONFIGURATION DO NOT REMOVE THIS LINE
		$this->table               = 'cms_users';
		$this->primary_key         = 'id';
		$this->title_field         = "name";
		$this->button_action_style = 'button_icon';	
		$this->button_import 	   = FALSE;	
		$this->button_export 	   = FALSE;	
		# END CONFIGURATION DO NOT REMOVE THIS LINE
	
		# START COLUMNS DO NOT REMOVE THIS LINE
		$this->col = array();
		$this->col[] = ["label" => "No", "callback_php" => '($row->index_number = (++$GLOBALS["index_number"]))'];
		$this->col[] = array("label"=>"Name","name"=>"name");
		$this->col[] = array("label"=>"Email","name"=>"email");
		$this->col[] = array("label"=>"Privilege","name"=>"id_cms_privileges","join"=>"cms_privileges,name");
		$this->col[] = array("label"=>"Kelurahan","name"=>"kelurahan_id","join"=>"kelurahans,nama_kelurahan");
		$this->col[] = array("label"=>"Photo","name"=>"photo","image"=>1);		
		$this->col[] = array("label"=>"No Whatsapp","name"=>"no_whatsapp");		
		$this->col[] = array("label"=>"Status","name"=>"status"); 

		// Kolom aktivitas: hanya untuk Super Admin
		if (CRUDBooster::myPrivilegeId() == 1) {
			$this->col[] = ["label" => "Total Transaksi (Rp)", "callback_php" => '"Rp ".number_format(\App\Http\Controllers\AdminCmsUsersController::bsuStat($row->id, "rp"), 0, ",", ".")'];
			$this->col[] = ["label" => "Total Berat (kg)", "callback_php" => 'number_format(\App\Http\Controllers\AdminCmsUsersController::bsuStat($row->id, "kg"), 2, ",", ".")'];
			$this->col[] = ["label" => "Transaksi Terakhir", "callback_php" => '\App\Http\Controllers\AdminCmsUsersController::bsuStat($row->id, "terakhir")'];
			$this->col[] = ["label" => "Aktivitas", "callback_php" => '\App\Http\Controllers\AdminCmsUsersController::bsuStat($row->id, "aktivitas")'];
		}
		# END COLUMNS DO NOT REMOVE THIS LINE

		# START FORM DO NOT REMOVE THIS LINE
		$this->form = array(); 		
		$this->form[] = array("label"=>"Name","name"=>"name",'required'=>true,'validation'=>'required|min:3');
		$this->form[] = array("label"=>"Email","name"=>"email",'required'=>true,'type'=>'email','validation'=>'required|email|unique:cms_users,email,'.CRUDBooster::getCurrentId());
		$this->form[] = array("label"=>"No Whatsapp","name"=>"no_whatsapp");		
		$this->form[] = array("label"=>"Photo","name"=>"photo","type"=>"upload","help"=>"Recommended resolution is 200x200px",'required'=>true,'validation'=>'required|image|max:1000','resize_width'=>90,'resize_height'=>90);
		$this->form[] = array("label"=>"Status","name"=>"status","type"=>"select","dataenum"=>"Active;Inactive",'required'=>true);							
		$this->form[] = [
			"label" => "Kelurahan",
			"name" => "kelurahan_id",
			"type" => "select",
			"datatable" => "kelurahans,nama_kelurahan",
			"required" => false,
			"help" => "Pemetaan wilayah BSU. Untuk akun BSI: kelurahan yang dipegang (satu BSI hanya satu kelurahan, satu kelurahan hanya satu BSI)."
		];
		$this->form[] = [
			"label" => "Privilege",
			"name" => "id_cms_privileges",
			"type" => "select",
			"datatable" => "cms_privileges,name",
			'required' => true,
			'datatable_where' => (CRUDBooster::myId() == 10) ? "id != 1 AND id != 2" : ""
		];						
		$this->form[] = array("label"=>"Password","name"=>"password","type"=>"password","help"=>"Please leave empty if not change");
		$this->form[] = array("label"=>"Password Confirmation","name"=>"password_confirmation","type"=>"password","help"=>"Please leave empty if not change");
		// Add status field with active/inactive options		
		# END FORM DO NOT REMOVE THIS LINE
		
		$this->load_css[] =  asset('ecobuana/bootstrap/css/admin.css');

		$this->script_js = "
			$(function() {
				function checkPrivilege() {
					var privText = $('select[name=id_cms_privileges] option:selected').text().trim();
					var privVal = $('select[name=id_cms_privileges]').val();
					
					// Ketika privilege adalah BSU (id 4 atau nama BSU)
					if (privText.toUpperCase().indexOf('BSU') !== -1 || privVal == '4' || privText.toUpperCase().trim() === 'BSI') {
						$('#form-group-kelurahan_id').slideDown(200);
						$('#kelurahan_id').prop('required', true);
						if ($('#form-group-kelurahan_id label .text-danger').length === 0) {
							$('#form-group-kelurahan_id label').append(' <span class=\"text-danger\" title=\"Field ini wajib diisi\">*</span>');
						}
					} else {
						$('#form-group-kelurahan_id').slideUp(200);
						$('#kelurahan_id').prop('required', false).val('');
						$('#form-group-kelurahan_id label .text-danger').remove();
					}
				}

				$('select[name=id_cms_privileges]').on('change', checkPrivilege);
				// In case select2 is used
				$('select[name=id_cms_privileges]').on('select2:select', checkPrivilege);

				// Jalankan pengecekan saat form dimuat
				checkPrivilege();
			});
		";

	}

	public function getProfile() {			

		$this->button_addmore = FALSE;
		$this->button_cancel  = FALSE;
		$this->button_show    = FALSE;			
		$this->button_add     = FALSE;
		$this->button_delete  = FALSE;	
		$this->hide_form 	  = ['id_cms_privileges', 'kelurahan_id'];

		$data['page_title'] = cbLang("label_button_profile");
		$data['row']        = CRUDBooster::first('cms_users',CRUDBooster::myId());

        return $this->view('crudbooster::default.form',$data);
	}
	public function hook_before_edit(&$postdata,$id) { 
		unset($postdata['password_confirmation']);
		$this->aturKelurahanSesuaiPeran($postdata, $id);
	}
	public function hook_before_add(&$postdata) {      
	    unset($postdata['password_confirmation']);
		$this->aturKelurahanSesuaiPeran($postdata, null);
	}

	/**
	 * Kelurahan hanya berlaku untuk BSU dan BSI (peran lain dikosongkan). Untuk BSI, satu kelurahan hanya
	 * boleh dipegang satu akun BSI (aturan yang sama dengan menu Akun BSI), sehingga halaman ini tidak bisa
	 * dipakai melewati aturan itu — dan kelurahan BSI tidak ikut terhapus saat akunnya diedit di sini.
	 */
	private function aturKelurahanSesuaiPeran(&$postdata, $id) {
		if (!isset($postdata['id_cms_privileges'])) {
			return;
		}
		$priv = DB::table('cms_privileges')->where('id', $postdata['id_cms_privileges'])->first();
		$adalahBsu = $priv && stripos($priv->name, 'BSU') !== false;
		$adalahBsi = $priv && strtoupper(trim($priv->name)) === 'BSI';
		if ($adalahBsi) {
			try {
				app(\App\Services\WilayahBsiService::class)->pastikanKelurahanTersedia($postdata['kelurahan_id'] ?? null, $id);
			} catch (\InvalidArgumentException $e) {
				CRUDBooster::redirect(CRUDBooster::mainpath(), $e->getMessage(), 'warning');
			}
		} elseif (!$adalahBsu) {
			$postdata['kelurahan_id'] = null;
		}
	}
	public static function bsuStat($id, $field)
	{
		static $stats = null;
		if ($stats === null) {
			$stats = DB::table('transactions')
				->select('admin_id',
					DB::raw('SUM(total_income) AS rp'),
					DB::raw('SUM(total_weight) AS kg'),
					DB::raw('MAX(created_at) AS terakhir'))
				->groupBy('admin_id')
				->get()
				->keyBy('admin_id');
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
			$query->where('cms_users.id', '!=', 1);
		}
	}
}
