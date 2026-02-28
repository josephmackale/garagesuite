<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_pack_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('garage_id')->index();
            $table->unsignedBigInteger('approval_pack_id')->index();

            // Snapshot of quotation line
            $table->string('line_type', 20)->default('part')->index(); // part|labour|fee|discount|other
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->decimal('qty', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);

            // Optional extras for later reporting (safe to keep nullable)
            $table->string('tax_code', 50)->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();

            // Where it came from (for traceability)
            $table->unsignedBigInteger('source_quotation_line_id')->nullable()->index();

            $table->timestamps();

            $table->foreign('approval_pack_id')
                ->references('id')->on('approval_packs')
                ->cascadeOnDelete();

            // Fast retrieval + protect from duplicates in one pack
            $table->index(['approval_pack_id', 'line_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_pack_items');
    }
};
