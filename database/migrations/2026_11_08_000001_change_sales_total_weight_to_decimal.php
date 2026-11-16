<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeSalesTotalWeightToDecimal extends Migration
{
    /**
     * Ubah sales.total_weight dari INT jadi DECIMAL(10,2)
     * supaya berat desimal (mis. 2.5 kg) tidak dibulatkan.
     */
    public function up()
    {
        DB::statement('ALTER TABLE sales MODIFY total_weight DECIMAL(10,2) NOT NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE sales MODIFY total_weight INT(11) NOT NULL');
    }
}