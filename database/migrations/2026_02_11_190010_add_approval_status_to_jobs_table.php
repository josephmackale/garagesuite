<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'approval_status')) {
                $table->string('approval_status', 20)->nullable()->index(); // pending|approved|rejected|null
            }
            if (!Schema::hasColumn('jobs', 'approval_submitted_at')) {
                $table->timestamp('approval_submitted_at')->nullable();
            }
            if (!Schema::hasColumn('jobs', 'approval_approved_at')) {
                $table->timestamp('approval_approved_at')->nullable();
            }
            if (!Schema::hasColumn('jobs', 'approval_rejected_at')) {
                $table->timestamp('approval_rejected_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (Schema::hasColumn('jobs', 'approval_status')) $table->dropColumn('approval_status');
            if (Schema::hasColumn('jobs', 'approval_submitted_at')) $table->dropColumn('approval_submitted_at');
            if (Schema::hasColumn('jobs', 'approval_approved_at')) $table->dropColumn('approval_approved_at');
            if (Schema::hasColumn('jobs', 'approval_rejected_at')) $table->dropColumn('approval_rejected_at');
        });
    }
};

