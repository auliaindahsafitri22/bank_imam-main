<?php namespace App\Http\Controllers;

	use Session;
	use Request;
	use DB;
	use CRUDBooster;
	use App\Services\ImporKatalogBsuService;
	use App\Services\HargaService;
	use App\Services\WilayahBsiService;
	use App\Http\Controllers\Concerns\CekPeran;

	class AdminSampahController extends \crocodicstudio\crudbooster\controllers\CBController {

	    use CekPeran;

	    /** Harga per kg sebelum diedit (untuk riwayat harga). */
	    protected $hargaLamaEdit = null;

	    public function cbInit() {

			# START CONFIGURATION DO NOT REMOVE THIS LINE
			$this->title_field = "name";
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
			$this->table = "sampah";
			# END CONFIGURATION DO NOT REMOVE THIS LINE

			# START COLUMNS DO NOT REMOVE THIS LINE
			$this->col = [];
			$this->col[] = ["label" => "No", "callback_php" => '($row->index_number = (++$GLOBALS["index_number"]))'];
			$this->col[] = ["label"=>"Nama Sampah","name"=>"name"];
			$this->col[] = ["label"=>"Kategori Sampah","name"=>"category_id","join"=>"sampah_categories,name"];
			$this->col[] = ["label"=>"Deskripsi","name"=>"deskripsi"];
			$this->col[] = ["label"=>"Gambar","name"=>"image","image"=>true];
			$this->col[] = ["label"=>"Harga per Kg","name"=>"price_per_kg",'callback_php' => '"Rp. ".number_format($row->price_per_kg)'];
			$this->col[] = ["label"=>"Poin per Kg","name"=>"poin_per_kg",'callback_php' => 'number_format($row->poin_per_kg)'];
			// Kolom harga acuan BSI & margin (diisi lewat tombol "Harga & Margin"). $row->harga_bsi/margin_type dipilih di hook_query_index.
			$this->col[] = ["label"=>"Harga Acuan BSI","name"=>"bsi_harga_id",'callback_php' => '$row->bsi_harga_id ? "Rp ".number_format($row->harga_bsi) : "-"'];
			$this->col[] = ["label"=>"BSI Sumber","name"=>"bsi_harga_id",'callback_php' => '$row->bsi_harga_id ? ($row->nama_bsi_sumber ?: "Harga Bersama") : "-"'];
			$this->col[] = ["label"=>"Margin","name"=>"margin_value",'callback_php' => '$row->bsi_harga_id ? ($row->margin_type == "persen" ? rtrim(rtrim(number_format($row->margin_value, 2, ".", ""), "0"), ".")."%" : "Rp ".number_format($row->margin_value)) : "-"'];
			$this->col[] = ["label"=>"Sumber Harga","name"=>"auto_sync",'callback_php' => '$row->bsi_harga_id ? ($row->auto_sync ? "Ikut harga BSI" : "Terhubung (manual)") : "Manual"'];
			# END COLUMNS DO NOT REMOVE THIS LINE

			# START FORM DO NOT REMOVE THIS LINE
			$this->form = [];
			$this->form[] = ['label'=>'Nama Sampah','name'=>'name','type'=>'text','validation'=>'required|string|min:3|max:70','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Kategori Sampah','name'=>'category_id','type'=>'select2','validation'=>'required|min:1|max:255','width'=>'col-sm-10','datatable'=>'sampah_categories,name', 'datatable_where' => 'admin_id = '.CRUDBooster::myId()];
			$this->form[] = ['label'=>'Deskripsi','name'=>'deskripsi','type'=>'text','validation'=>'string|min:3|max:200','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Gambar','name'=>'image','type'=>'upload','validation'=>'required|image|max:20000','width'=>'col-sm-10','help'=>'File types support : JPG, JPEG, PNG, GIF, BMP'];
			$this->form[] = ['label'=>'Harga Per Kg','name'=>'price_per_kg','type'=>'number','validation'=>'required|integer|min:0','width'=>'col-sm-10','help'=>'Jika sampah terhubung ke harga BSI dan ikut harga BSI, harga dihitung otomatis (harga BSI - margin). Atur lewat tombol Harga & Margin.'];
			$this->form[] = ['label'=>'Poin Per Kg','name'=>'poin_per_kg','type'=>'number','validation'=>'required|integer|min:0','width'=>'col-sm-10'];
			$this->form[] = ['label' => 'Nama BSU', 'name' => 'admin_id', 'type' => 'hidden', 'validation' => 'required|integer|min:0', 'width' => 'col-sm-10', 'value' => CRUDBooster::myId()];
			# END FORM DO NOT REMOVE THIS LINE

			# OLD START FORM
			//$this->form = [];
			//$this->form[] = ['label'=>'Nama Sampah','name'=>'name','type'=>'text','validation'=>'required|string|min:3|max:70','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Kategori Sampah','name'=>'category_id','type'=>'select2','validation'=>'required|min:1|max:255','width'=>'col-sm-10','datatable'=>'sampah_categories,name'];
			//$this->form[] = ['label'=>'Image','name'=>'image','type'=>'upload','validation'=>'required|image|max:3000','width'=>'col-sm-10','help'=>'File types support : JPG, JPEG, PNG, GIF, BMP'];
			//$this->form[] = ['label'=>'Price Per Kg','name'=>'price_per_kg','type'=>'number','validation'=>'required|integer|min:0','width'=>'col-sm-10'];
			# OLD END FORM

			/* 
	        | ---------------------------------------------------------------------- 
	        | Sub Module
	        | ----------------------------------------------------------------------     
			| @label          = Label of action 
			| @path           = Path of sub module
			| @foreign_key 	  = foreign key of sub table/module
			| @button_color   = Bootstrap Class (primary,success,warning,danger)
			| @button_icon    = Font Awesome Class  
			| @parent_columns = Sparate with comma, e.g : name,created_at
	        | 
	        */
	        $this->sub_module = array();


	        /* 
	        | ---------------------------------------------------------------------- 
	        | Add More Action Button / Menu
	        | ----------------------------------------------------------------------     
	        | @label       = Label of action 
	        | @url         = Target URL, you can use field alias. e.g : [id], [name], [title], etc
	        | @icon        = Font awesome class icon. e.g : fa fa-bars
	        | @color 	   = Default is primary. (primary, warning, succecss, info)     
	        | @showIf 	   = If condition when action show. Use field alias. e.g : [id] == 1
	        | 
	        */
	        $this->addaction = array();


	        /* 
	        | ---------------------------------------------------------------------- 
	        | Add More Button Selected
	        | ----------------------------------------------------------------------     
	        | @label       = Label of action 
	        | @icon 	   = Icon from fontawesome
	        | @name 	   = Name of button 
	        | Then about the action, you should code at actionButtonSelected method 
	        | 
	        */
	        $this->button_selected = array();

	                
	        /* 
	        | ---------------------------------------------------------------------- 
	        | Add alert message to this module at overheader
	        | ----------------------------------------------------------------------     
	        | @message = Text of message 
	        | @type    = warning,success,danger,info        
	        | 
	        */
	        $this->alert        = array();
	                

	        
	        /* 
	        | ---------------------------------------------------------------------- 
	        | Add more button to header button 
	        | ----------------------------------------------------------------------     
	        | @label = Name of button 
	        | @url   = URL Target
	        | @icon  = Icon from Awesome.
	        | 
	        */
	        $this->index_button = array();

	        // Tombol impor katalog dari BSU Mercu Buana (hanya untuk akun BSU).
	        if ((int) CRUDBooster::myPrivilegeId() === 4) {
	        	$this->index_button[] = ['label' => 'Harga & Margin', 'url' => CRUDBooster::mainpath('harga'), 'icon' => 'fa fa-tags', 'color' => 'success'];
	        	$this->index_button[] = ['label' => 'Riwayat Harga', 'url' => CRUDBooster::mainpath('harga-riwayat'), 'icon' => 'fa fa-history', 'color' => 'default'];
	        	$this->index_button[] = ['label' => 'Impor Katalog dari BSU Mercu Buana', 'url' => CRUDBooster::mainpath('impor-katalog'), 'icon' => 'fa fa-download', 'color' => 'warning'];
	        }




	        /* 
	        | ---------------------------------------------------------------------- 
	        | Customize Table Row Color
	        | ----------------------------------------------------------------------     
	        | @condition = If condition. You may use field alias. E.g : [id] == 1
	        | @color = Default is none. You can use bootstrap success,info,warning,danger,primary.        
	        | 
	        */
	        $this->table_row_color = array();     	          

	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | You may use this bellow array to add statistic at dashboard 
	        | ---------------------------------------------------------------------- 
	        | @label, @count, @icon, @color 
	        |
	        */
	        $this->index_statistic = array();



	        /*
	        | ---------------------------------------------------------------------- 
	        | Add javascript at body 
	        | ---------------------------------------------------------------------- 
	        | javascript code in the variable 
	        | $this->script_js = "function() { ... }";
	        |
	        */
	        $this->script_js = NULL;


            /*
	        | ---------------------------------------------------------------------- 
	        | Include HTML Code before index table 
	        | ---------------------------------------------------------------------- 
	        | html code to display it before index table
	        | $this->pre_index_html = "<p>test</p>";
	        |
	        */
	        $this->pre_index_html = null;
	        
	        
	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | Include HTML Code after index table 
	        | ---------------------------------------------------------------------- 
	        | html code to display it after index table
	        | $this->post_index_html = "<p>test</p>";
	        |
	        */
	        $this->post_index_html = null;
	        
	        
	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | Include Javascript File 
	        | ---------------------------------------------------------------------- 
	        | URL of your javascript each array 
	        | $this->load_js[] = asset("myfile.js");
	        |
	        */
	        $this->load_js = array();
	        
	        
	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | Add css style at body 
	        | ---------------------------------------------------------------------- 
	        | css code in the variable 
	        | $this->style_css = ".style{....}";
	        |
	        */
	        $this->style_css = NULL;
	        
	        
	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | Include css File 
	        | ---------------------------------------------------------------------- 
	        | URL of your css each array 
	        | $this->load_css[] = asset("myfile.css");
	        |
	        */
	        $this->load_css[] =  asset('ecobuana/bootstrap/css/admin.css');
	        
	        
	    }


	    /*
	    | ---------------------------------------------------------------------- 
	    | Hook for button selected
	    | ---------------------------------------------------------------------- 
	    | @id_selected = the id selected
	    | @button_name = the name of button
	    |
	    */
	    public function actionButtonSelected($id_selected,$button_name) {
	        //Your code here
	            
	    }


	    /*
	    | ---------------------------------------------------------------------- 
	    | Hook for manipulate query of index result 
	    | ---------------------------------------------------------------------- 
	    | @query = current sql query 
	    |
	    */
	    public function hook_query_index(&$query) {
	        $currentUserId = CRUDBooster::myId();
	        
	        $GLOBALS['index_number'] = 0;
	        $query->addSelect('sampah.margin_type', 'sampah.margin_value', 'sampah.auto_sync', 'sampah.bsi_harga_id',
	        	DB::raw('(select b.harga from bsi_harga b where b.id = sampah.bsi_harga_id) as harga_bsi'),
	        	DB::raw('(select u.name from bsi_harga b join cms_users u on u.id = b.bsi_id where b.id = sampah.bsi_harga_id) as nama_bsi_sumber'));
	        
			if ($currentUserId == 1) {

			}elseif($currentUserId == 10) {
				
			}
			else {
				$query->where('sampah.admin_id', $currentUserId);
			}
	    }

	    /*
	    | ---------------------------------------------------------------------- 
	    | Hook for manipulate row of index table html 
	    | ---------------------------------------------------------------------- 
	    |
	    */    
	    public function hook_row_index($column_index,&$column_value) {	        
	    	//Your code here
	    }

	    /*
	    | ---------------------------------------------------------------------- 
	    | Hook for manipulate data input before add data is execute
	    | ---------------------------------------------------------------------- 
	    | @arr
	    |
	    */
	    public function hook_before_add(&$postdata) {        
	        //Your code here

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for execute command after add public static function called 
	    | ---------------------------------------------------------------------- 
	    | @id = last insert id
	    | 
	    */
	    public function hook_after_add($id) {        
	        // Catat harga awal supaya Riwayat Harga lengkap sejak sampah dibuat.
	        $s = DB::table('sampah')->where('id', $id)->first();
	        if ($s) {
	        	app(HargaService::class)->catatHargaManual($s->id, $s->admin_id, null, null, (int) $s->price_per_kg, CRUDBooster::myId(), 'Jenis sampah ditambahkan', 'awal');
	        }

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for manipulate data input before update data is execute
	    | ---------------------------------------------------------------------- 
	    | @postdata = input post data 
	    | @id       = current id 
	    | 
	    */
	    public function hook_before_edit(&$postdata,$id) {        
	        $s = DB::table('sampah')->where('id', $id)->first();
	        $this->hargaLamaEdit = $s ? (int) $s->price_per_kg : null;
	        // Terhubung ke BSI dan ikut harga BSI: harga selalu dihitung dari harga BSI - margin,
	        // sehingga isian manual tidak tertimpa diam-diam saat harga BSI berubah.
	        if ($s && $s->bsi_harga_id && (int) $s->auto_sync === 1) {
	        	$bsi = DB::table('bsi_harga')->where('id', $s->bsi_harga_id)->first();
	        	if ($bsi) {
	        		$postdata['price_per_kg'] = HargaService::hitungHarga($bsi->harga, $s->margin_type, $s->margin_value);
	        	}
	        }

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for execute command after edit public static function called
	    | ----------------------------------------------------------------------     
	    | @id       = current id 
	    | 
	    */
	    public function hook_after_edit($id) {
	        $s = DB::table('sampah')->where('id', $id)->first();
	        if (!$s || $this->hargaLamaEdit === null) {
	        	return;
	        }
	        $lama = $this->hargaLamaEdit;
	        $baru = (int) $s->price_per_kg;
	        if ($baru === $lama) {
	        	return;
	        }
	        // Terhubung tetapi tidak ikut BSI (manual): selaraskan margin supaya kolom Margin tetap sesuai harga.
	        if ($s->bsi_harga_id && (int) $s->auto_sync === 0) {
	        	$hargaBsi = (int) DB::table('bsi_harga')->where('id', $s->bsi_harga_id)->value('harga');
	        	DB::table('sampah')->where('id', $s->id)->update(['margin_type' => 'nominal', 'margin_value' => max(0, $hargaBsi - $baru)]);
	        }
	        app(HargaService::class)->catatHargaManual($s->id, $s->admin_id, $s->bsi_harga_id, $lama, $baru, CRUDBooster::myId(), 'Diubah di Master Sampah');

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for execute command before delete public static function called
	    | ----------------------------------------------------------------------     
	    | @id       = current id 
	    | 
	    */
	    public function hook_before_delete($id) {
	        //Your code here

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for execute command after delete public static function called
	    | ----------------------------------------------------------------------     
	    | @id       = current id 
	    | 
	    */
	    public function hook_after_delete($id) {
	        //Your code here

	    }




	    /** Pratinjau impor katalog (kategori, nama, harga) dari BSU Mercu Buana ke akun BSU yang login. */
	    public function getImporKatalog() {
	        $this->cbLoader();
	        $this->wajibAkunBsu();
	        try {
	            $pratinjau = app(ImporKatalogBsuService::class)->rencana((int) CRUDBooster::myId());
	        } catch (\InvalidArgumentException $e) {
	            CRUDBooster::redirect(CRUDBooster::mainpath(), $e->getMessage(), 'warning');
	        }
	        return view('admin.sampah_impor_katalog', ['page_title' => 'Impor Katalog Sampah dari BSU Mercu Buana', 'pratinjau' => $pratinjau]);
	    }

	    /** Menyimpan impor katalog. Hanya ke akun BSU yang sedang login. */
	    public function postImporKatalog() {
	        $this->cbLoader();
	        $this->wajibAkunBsu();
	        try {
	            $hasil = app(ImporKatalogBsuService::class)->terapkan((int) CRUDBooster::myId());
	        } catch (\InvalidArgumentException $e) {
	            CRUDBooster::redirect(CRUDBooster::mainpath(), $e->getMessage(), 'warning');
	        }
	        CRUDBooster::insertLog('Impor katalog sampah dari ' . $hasil['sumber'] . ': ' . json_encode($hasil));
	        CRUDBooster::redirect(
	            CRUDBooster::mainpath(),
	            'Impor selesai: ' . $hasil['jenis_ditambah'] . ' jenis sampah dan ' . $hasil['kategori_ditambah'] . ' kategori baru ditambahkan; ' . $hasil['dilewati'] . ' jenis dilewati karena sudah ada.',
	            'success'
	        );
	    }

	    // ---- Harga & Margin (sebelumnya menu terpisah "Harga & Margin"; kini bagian dari Master Sampah) ----

	    private function hargaBsuId() {
	        $this->wajibPeran([4]);
	        return (int) CRUDBooster::myId();
	    }

	    /** Halaman atur margin, aktifkan jenis BSI, hubungkan/putuskan sampah. */
	    public function getHarga() {
	        $adminId = $this->hargaBsuId();
	        $this->cbLoader();

	        // Hanya harga dari BSI kelurahan BSU ini (atau Harga Bersama bila kelurahannya belum punya BSI).
	        $wilayah = app(WilayahBsiService::class);
	        $pemilik = $wilayah->pemilikHargaBsu($adminId);
	        $sumberBsi = $wilayah->bsiUntukBsu($adminId);
	        $kelId = DB::table('cms_users')->where('id', $adminId)->value('kelurahan_id');
	        $master = WilayahBsiService::batasiPemilik(DB::table('bsi_harga')->where('is_active', 1)->whereNull('dihapus_at'), $pemilik)
	            ->orderBy('kategori')->orderBy('nama')->get();
	        $milik = DB::table('sampah')->where('admin_id', $adminId)->whereNotNull('bsi_harga_id')->get()->keyBy('bsi_harga_id');

	        $baris = [];
	        foreach ($master as $m) {
	            $sm = $milik->get($m->id);
	            $baris[] = (object) [
	                'master' => $m, 'sampah' => $sm,
	                'harga_nasabah' => $sm ? (int) $sm->price_per_kg : null,
	            ];
	        }
	        $belumTerhubung = DB::table('sampah')->where('admin_id', $adminId)->whereNull('bsi_harga_id')
	            ->orderBy('name')->get(['id', 'name', 'price_per_kg']);

	        $perubahanBaru = WilayahBsiService::batasiPemilik(
	            DB::table('bsi_harga_riwayat as r')->join('bsi_harga as b', 'b.id', '=', 'r.bsi_harga_id'), $pemilik, 'b.bsi_id')
	            ->whereNull('b.dihapus_at')->whereNotNull('r.harga_lama')->where('r.tanggal', '>=', now()->subDays(14)->toDateString())
	            ->orderByDesc('r.id')->limit(8)->get(['b.nama', 'r.harga_lama', 'r.harga_baru', 'r.tanggal']);

	        // Sambungan lama ke harga BSI lain (mis. BSU pindah kelurahan): ditampilkan sebagai peringatan.
	        $sambunganLain = DB::table('sampah as s')->join('bsi_harga as b', 'b.id', '=', 's.bsi_harga_id')
	            ->where('s.admin_id', $adminId)
	            ->where(function ($w) use ($pemilik) {
	                if ($pemilik === null) { $w->whereNotNull('b.bsi_id'); }
	                else { $w->whereNull('b.bsi_id')->orWhere('b.bsi_id', '!=', $pemilik); }
	            })->count();

	        return view('admin.harga_bsu', [
	            'page_title' => 'Harga & Margin Sampah',
	            'baris' => $baris, 'belumTerhubung' => $belumTerhubung,
	            'master' => $master, 'perubahanBaru' => $perubahanBaru,
	            'sumberBsi' => $sumberBsi, 'sumberKelurahan' => $kelId ? DB::table('kelurahans')->where('id', $kelId)->value('nama_kelurahan') : null,
	            'sambunganLain' => $sambunganLain,
	        ]);
	    }

	    public function postHargaSimpan() {
	        $adminId = $this->hargaBsuId();
	        $svc = app(HargaService::class);
	        $data = (array) request('margin', []);
	        $ok = 0; $gagal = [];
	        foreach ($data as $sampahId => $f) {
	            try {
	                $svc->aturMargin((int) $sampahId, $adminId, $f['type'] ?? 'nominal', $f['value'] ?? 0, !empty($f['auto']), $adminId);
	                $ok++;
	            } catch (\Throwable $e) {
	                $gagal[] = '#' . (int) $sampahId . ': ' . $e->getMessage();
	            }
	        }
	        $pesan = "Margin $ok jenis sampah disimpan." . ($gagal ? ' Gagal: ' . implode('; ', $gagal) : '');
	        CRUDBooster::redirect(CRUDBooster::mainpath('harga'), $pesan, $gagal ? 'warning' : 'success');
	    }

	    public function postHargaAktifkan() {
	        $adminId = $this->hargaBsuId();
	        try {
	            app(HargaService::class)->aktifkan($adminId, (int) request('bsi_harga_id'), request('type', 'nominal'), request('value', 0), $adminId);
	        } catch (\Throwable $e) {
	            CRUDBooster::redirect(CRUDBooster::mainpath('harga'), $e->getMessage(), 'warning');
	        }
	        CRUDBooster::redirect(CRUDBooster::mainpath('harga'), 'Jenis sampah diaktifkan.', 'success');
	    }

	    public function postHargaHubungkan() {
	        $adminId = $this->hargaBsuId();
	        try {
	            $m = app(HargaService::class)->hubungkan($adminId, (int) request('sampah_id'), (int) request('bsi_harga_id'), $adminId);
	        } catch (\Throwable $e) {
	            CRUDBooster::redirect(CRUDBooster::mainpath('harga'), $e->getMessage(), 'warning');
	        }
	        CRUDBooster::redirect(CRUDBooster::mainpath('harga'), 'Terhubung. Harga beli nasabah tetap; margin awal ' . $this->rupiah($m) . '/kg.', 'success');
	    }

	    public function postHargaPutuskan() {
	        $adminId = $this->hargaBsuId();
	        app(HargaService::class)->putuskan($adminId, (int) request('sampah_id'));
	        CRUDBooster::redirect(CRUDBooster::mainpath('harga'), 'Sambungan ke harga BSI diputus. Harga kini diatur manual.', 'success');
	    }

	    public function getHargaRiwayat() {
	        $adminId = $this->hargaBsuId();
	        $this->cbLoader();
	        $dari = request('dari'); $sampai = request('sampai');
	        $q = app(HargaService::class)->riwayatBsu($adminId, 1000);
	        if ($dari) { $q = $q->filter(function ($r) use ($dari) { return $r->tanggal >= $dari; }); }
	        if ($sampai) { $q = $q->filter(function ($r) use ($sampai) { return $r->tanggal <= $sampai; }); }
	        return view('admin.harga_bsu_riwayat', [
	            'page_title' => 'Riwayat Perubahan Harga', 'rows' => $q->values(), 'dari' => $dari, 'sampai' => $sampai,
	        ]);
	    }

	    private function wajibAkunBsu() {
	        if ((int) CRUDBooster::myPrivilegeId() !== 4) {
	            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
	        }
	    }

	    //By the way, you can still create your own method in here... :) 


	}