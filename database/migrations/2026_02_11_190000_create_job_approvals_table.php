<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_approvals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('garage_id')->index();
            $table->unsignedBigInteger('job_id')->index();

            // Optional link to quotation row (recommended)
            $table->unsignedBigInteger('quotation_id')->nullable()->index();

            // Approval metadata
            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected
            $table->string('approved_by')->nullable();
            $table->string('approval_ref')->nullable(); // LPO / email ref / code
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('actioned_by')->nullable()->index();

            $table->timestamps();

            // If you already have garages/jobs tables, enable these:
            // $table->foreign('job_id')->references('id')->on('jobs')->cascadeOnDelete();
            // $table->foreign('quotation_id')->references('id')->on('job_quotations')->nullOnDelete();

            // Prevent accidental duplicates of same job in same garage (latest approval row model)
            $table->unique(['garage_id', 'job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_approvals');
    }
};
