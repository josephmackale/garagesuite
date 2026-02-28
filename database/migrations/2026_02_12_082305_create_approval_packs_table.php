<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_packs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('garage_id')->index();
            $table->unsignedBigInteger('job_id')->index();
            $table->unsignedBigInteger('quotation_id')->nullable()->index();

            $table->string('status', 20)->default('draft')->index(); // draft|submitted|approved|rejected|revised
            $table->unsignedInteger('version')->default(1);

            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('KES');

            $table->unsignedBigInteger('generated_by')->nullable()->index();

            $table->timestamp('generated_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->text('decision_notes')->nullable();

            $table->timestamps();

            // Optional FKs (enable if your schema has these tables/keys)
            // $table->foreign('garage_id')->references('id')->on('garages')->cascadeOnDelete();
            // $table->foreign('job_id')->references('id')->on('jobs')->cascadeOnDelete();
            // $table->foreign('quotation_id')->references('id')->on('job_quotations')->nullOnDelete();
            // $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();

            // Prevent duplicate active submissions for the same job/version
            $table->unique(['garage_id', 'job_id', 'version'], 'approval_packs_job_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_packs');
    }
};
