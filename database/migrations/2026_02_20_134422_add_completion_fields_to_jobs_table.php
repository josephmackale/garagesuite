<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');

            $table->index(['garage_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'completed_at']);
            $table->dropColumn(['completed_at', 'completed_by']);
        });
    }
};
