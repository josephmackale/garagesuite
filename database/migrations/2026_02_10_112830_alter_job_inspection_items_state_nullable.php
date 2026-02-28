<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_inspection_items', function (Blueprint $table) {
            // remove default + allow null
            $table->string('state', 20)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_inspection_items', function (Blueprint $table) {
            // revert to your old behavior
            $table->string('state', 20)->nullable(false)->default('ok')->change();
        });
    }
};
