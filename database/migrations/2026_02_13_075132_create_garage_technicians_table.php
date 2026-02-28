<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('garage_technicians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('garage_id')->index();

            $table->string('name');
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true)->index();

            $table->timestamps();

            $table->index(['garage_id', 'active']);
            $table->unique(['garage_id', 'name']); // prevents duplicates per garage
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garage_technicians');
    }
};
