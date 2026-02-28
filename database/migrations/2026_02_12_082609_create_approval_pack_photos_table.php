<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_pack_photos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('garage_id')->index();
            $table->unsignedBigInteger('approval_pack_id')->index();

            // Snapshot of media item
            $table->unsignedBigInteger('media_item_id')->nullable()->index(); // if using your Vault/media_items
            $table->string('category', 50)->nullable()->index(); // e.g. inspection, before, after
            $table->string('label', 255)->nullable();

            // Store what insurer needs even if media changes later
            $table->string('storage_disk', 50)->nullable();
            $table->string('storage_path', 500)->nullable();

            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->unsignedInteger('sort_order')->default(0)->index();

            // Traceability
            $table->unsignedBigInteger('source_attachment_id')->nullable()->index();

            $table->timestamps();

            $table->foreign('approval_pack_id')
                ->references('id')->on('approval_packs')
                ->cascadeOnDelete();

            $table->index(['approval_pack_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_pack_photos');
    }
};
