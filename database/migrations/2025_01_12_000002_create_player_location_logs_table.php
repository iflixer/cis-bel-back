<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerLocationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_location_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('country_id')->unsigned()->nullable();
            $table->integer('video_id')->nullable();
            $table->integer('domain_id')->unsigned()->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            
            $table->foreign('country_id')->references('id')->on('countries_iso')->onDelete('set null');
            $table->foreign('video_id')->references('id')->on('videos')->onDelete('set null');
            $table->foreign('domain_id')->references('id')->on('domains')->onDelete('set null');
            
            $table->index(['created_at', 'country_id']);
            $table->index(['created_at', 'video_id']);
            $table->index(['created_at', 'domain_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('player_location_logs');
    }
}