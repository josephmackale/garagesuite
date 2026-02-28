<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_quotation_lines', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('quotation_id')->index();

            // fixed internal types for reporting
            $table->string('type', 20)->default('labour'); // labour|parts|materials|sublet

            // optional extra label (future flexibility)
            $table->string('category', 100)->nullable();

            // Dalima-facing fields
            $table->string('description', 255);
            $table->decimal('qty', 12, 2)->default(1);

            // keep both for sanity; UI can be amount-first
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0); // line total (what Dalima prints)

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Optional FK (enable if your environment is consistent with FK constraints)
            // $table->foreign('quotation_id')->references('id')->on('job_quotations')->onDelete('cascade');

            $table->index(['quotation_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_quotation_lines');
    }
};
