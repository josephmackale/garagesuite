<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();

            // NULL = global/system fallback, else garage-specific
            $table->unsignedBigInteger('garage_id')->nullable()->index();

            // simple keying (v1: keep "default")
            $table->string('key')->default('default');

            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // HTML template + optional CSS
            $table->longText('body_html');
            $table->longText('css')->nullable();

            $table->timestamps();

            $table->unique(['garage_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_templates');
    }
};
