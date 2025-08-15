<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCountriesTable extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        Schema::create('countries_iso', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('iso_code', 2)->unique();
            $table->index('iso_code');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::drop('countries_iso');
    }
}