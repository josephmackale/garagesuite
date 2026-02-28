<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_insurance_details', function (Blueprint $table) {

            if (!Schema::hasColumn('job_insurance_details', 'claim_pack_generated_at')) {
                $table->timestamp('claim_pack_generated_at')->nullable()->after('claim_submitted_by');
            }

            if (!Schema::hasColumn('job_insurance_details', 'claim_pack_last_filename')) {
                $table->string('claim_pack_last_filename')->nullable()->after('claim_pack_generated_at');
            }

        });
    }

    public function down(): void
    {
        Schema::table('job_insurance_details', function (Blueprint $table) {

            if (Schema::hasColumn('job_insurance_details', 'claim_pack_generated_at')) {
                $table->dropColumn('claim_pack_generated_at');
            }

            if (Schema::hasColumn('job_insurance_details', 'claim_pack_last_filename')) {
                $table->dropColumn('claim_pack_last_filename');
            }

        });
    }
};