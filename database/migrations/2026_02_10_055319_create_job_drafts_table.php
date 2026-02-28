<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_drafts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('garage_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->uuid('draft_uuid')->unique();

            // quick resume context
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('vehicle_id')->nullable()->index();

            $table->string('payer_type', 30)->nullable()->index(); // individual|company|insurance
            $table->json('payer')->nullable();
            $table->json('details')->nullable();

            $table->string('last_step', 30)->nullable()->index(); // step1|step2|step3|quotation|review
            $table->string('status', 20)->default('draft')->index(); // draft|submitted|abandoned

            // optional linking after job creation
            $table->unsignedBigInteger('job_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_drafts');
    }
};
