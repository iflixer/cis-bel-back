<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubtitlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subtitles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('file_id')->unsigned();
            $table->integer('track_num')->unsigned()->default(0);
            $table->string('lang', 10);
            $table->string('subtitle_type', 50)->default('stream');
            $table->string('filename', 255);
            $table->string('url', 500);
            $table->timestamps();

            $table->index('file_id');
            $table->unique(['file_id', 'track_num']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('subtitles');
    }
}
