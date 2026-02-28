<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_quotations', function (Blueprint $table) {
            $table->bigIncrements('id');

            // multi-tenant scope
            $table->unsignedBigInteger('garage_id')->index();

            // link to job
            $table->unsignedBigInteger('job_id')->index();

            // lifecycle
            $table->string('status', 20)->default('draft'); // draft|submitted|approved|rejected
            $table->unsignedInteger('version')->default(1);

            // totals
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // submission/approval metadata (optional now, needed later)
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamps();

            // one "current quotation" per job per garage (v1)
            $table->unique(['garage_id', 'job_id', 'version'], 'uq_job_quotation_current');
            $table->index(['garage_id', 'job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_quotations');
    }
};
