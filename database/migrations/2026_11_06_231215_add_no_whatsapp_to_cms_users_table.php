<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoWhatsappToCmsUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('cms_users', 'no_whatsapp')) {
            Schema::table('cms_users', function (Blueprint $table) {
                $table->string('no_whatsapp', 20)->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('cms_users', 'no_whatsapp')) {
            Schema::table('cms_users', function (Blueprint $table) {
                $table->dropColumn('no_whatsapp');
            });
        }
    }
}