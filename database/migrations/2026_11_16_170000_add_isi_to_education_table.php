<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsiToEducationTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('education', 'isi')) {
            Schema::table('education', function (Blueprint $table) {
                $table->longText('isi')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('education', 'isi')) {
            Schema::table('education', function (Blueprint $table) {
                $table->dropColumn('isi');
            });
        }
    }
}