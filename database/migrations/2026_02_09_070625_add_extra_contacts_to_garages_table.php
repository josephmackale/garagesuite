<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('garages', function (Blueprint $table) {
            $table->string('phone2')->nullable()->after('phone');
            $table->string('phone3')->nullable()->after('phone2');
            $table->string('kra_pin')->nullable()->after('email');
        });
    }

    public function down()
    {
        Schema::table('garages', function (Blueprint $table) {
            $table->dropColumn(['phone2','phone3','kra_pin']);
        });
    }

};
