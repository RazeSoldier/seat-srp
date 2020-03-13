<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddKmInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('seat_srp_srp', function (Blueprint $table) {
            $table->string('user_name')->nullable();
            $table->string('corp_id')->nullable();
            $table->string('corp_name')->nullable();
            $table->dateTime('kill_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('seat_srp_srp', function (Blueprint $table) {
            $table->dropColumn('user_name');
            $table->dropColumn('corp_id');
            $table->dropColumn('corp_name');
            $table->dropColumn('kill_time');
        });
    }
}
