<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('schedules', function (Blueprint $table) {
        $table->time('start_time')->nullable()->change();
        $table->time('end_time')->nullable()->change();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('schedules', function (Blueprint $table) {
        $table->dateTime('start_time')->nullable()->change();
        $table->dateTime('end_time')->nullable()->change();
    });
}
};
