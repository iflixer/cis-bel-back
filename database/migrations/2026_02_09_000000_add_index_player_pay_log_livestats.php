<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexPlayerPayLogLivestats extends Migration
{
    public function up()
    {
        Schema::table('player_pay_log', function (Blueprint $table) {
            $table->index(['domain_id', 'event', 'created_at'], 'idx_ppl_domain_event_created');
        });
    }

    public function down()
    {
        Schema::table('player_pay_log', function (Blueprint $table) {
            $table->dropIndex('idx_ppl_domain_event_created');
        });
    }
}
