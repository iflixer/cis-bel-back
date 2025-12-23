<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBlacklistedToVideosTable extends Migration
{
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->boolean('blacklisted')->nullable()->default(null)->after('update_openai');
            $table->index('blacklisted');
        });
    }

    public function down()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['blacklisted']);
            $table->dropColumn('blacklisted');
        });
    }
}
