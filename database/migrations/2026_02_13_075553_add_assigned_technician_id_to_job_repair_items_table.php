<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_repair_items', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_technician_id')
                ->nullable()
                ->after('assigned_to')
                ->index();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_repair_items', function (Blueprint $table) {
            $table->dropColumn('assigned_technician_id');
        });
    }

};
