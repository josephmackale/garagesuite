<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_insurance_details', function (Blueprint $table) {
            if (!Schema::hasColumn('job_insurance_details', 'claim_pack_version')) {
                $table->unsignedInteger('claim_pack_version')->nullable()->after('claim_number');
            }
            if (!Schema::hasColumn('job_insurance_details', 'claim_pack_path')) {
                $table->string('claim_pack_path', 500)->nullable()->after('claim_pack_version');
            }
            if (!Schema::hasColumn('job_insurance_details', 'claim_submitted_at')) {
                $table->timestamp('claim_submitted_at')->nullable()->after('claim_pack_path');
            }
            if (!Schema::hasColumn('job_insurance_details', 'claim_submitted_by')) {
                $table->unsignedBigInteger('claim_submitted_by')->nullable()->after('claim_submitted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_insurance_details', function (Blueprint $table) {
            $cols = ['claim_pack_version','claim_pack_path','claim_submitted_at','claim_submitted_by'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('job_insurance_details', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};