<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media_links', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('garage_id')->index();
            $table->unsignedBigInteger('media_item_id')->index();

            $table->string('model_type'); // e.g. App\Models\Job
            $table->unsignedBigInteger('model_id');

            $table->string('collection')->default('default')->index(); // inspection_photos, completion_photos, etc.

            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->unique(['garage_id', 'media_item_id', 'model_type', 'model_id', 'collection'], 'media_links_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_links');
    }
};