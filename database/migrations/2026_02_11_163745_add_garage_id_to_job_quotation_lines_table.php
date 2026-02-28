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
        Schema::table('job_quotation_lines', function (Blueprint $table) {
            // Add garage_id for multi-tenant scoping
            $table->unsignedBigInteger('garage_id')
                  ->nullable()
                  ->after('id')
                  ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_quotation_lines', function (Blueprint $table) {
            $table->dropColumn('garage_id');
        });
    }
};
