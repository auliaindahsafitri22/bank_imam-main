<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangePictureColumnToLongtextInUsersTable extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE users MODIFY picture LONGTEXT NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE users MODIFY picture VARCHAR(255) NULL');
    }
}