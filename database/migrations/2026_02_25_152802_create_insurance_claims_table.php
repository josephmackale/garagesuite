<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('garage_id')->index();
            $table->unsignedBigInteger('job_id')->index();

            $table->string('claim_number', 32)->nullable()->index(); // e.g. CLM-2026-00001
            $table->string('status', 32)->default('submitted')->index(); // v1: submitted only

            $table->unsignedBigInteger('approval_pack_id')->nullable()->index();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();

            $table->text('notes')->nullable();

            $table->timestamp('submitted_at')->nullable()->index();
            $table->unsignedBigInteger('submitted_by')->nullable()->index();

            $table->timestamps();

            // STRICT v1: ONE claim per job per garage
            $table->unique(['garage_id', 'job_id'], 'uniq_claim_per_job');
            $table->unique(['garage_id', 'claim_number'], 'uniq_claim_number_per_garage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};