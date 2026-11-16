<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCmsUserIdToUsersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'cms_user_id')) {
            Schema::table('users', function (Blueprint $t) {
                $t->unsignedInteger('cms_user_id')->nullable()->index();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'cms_user_id')) {
            Schema::table('users', function (Blueprint $t) {
                $t->dropColumn('cms_user_id');
            });
        }
    }
}