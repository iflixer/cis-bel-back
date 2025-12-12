<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddKinopoiskDevFieldsToVideosTable extends Migration
{
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('alternative_name', 255)->nullable()->default(null)->after('ru_name');
            $table->tinyInteger('update_kinopoisk_dev')->default(0)->after('update_openai');
        });
    }

    public function down()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('alternative_name');
            $table->dropColumn('update_kinopoisk_dev');
        });
    }
}
