<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_insurance_details', function (Blueprint $table) {
            if (!Schema::hasColumn('job_insurance_details', 'claim_submitted_at')) {
                $table->timestamp('claim_submitted_at')->nullable()->after('claim_pack_version');
            }
            if (!Schema::hasColumn('job_insurance_details', 'claim_submitted_by')) {
                $table->unsignedBigInteger('claim_submitted_by')->nullable()->after('claim_submitted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_insurance_details', function (Blueprint $table) {
            if (Schema::hasColumn('job_insurance_details', 'claim_submitted_by')) {
                $table->dropColumn('claim_submitted_by');
            }
            if (Schema::hasColumn('job_insurance_details', 'claim_submitted_at')) {
                $table->dropColumn('claim_submitted_at');
            }
        });
    }
};