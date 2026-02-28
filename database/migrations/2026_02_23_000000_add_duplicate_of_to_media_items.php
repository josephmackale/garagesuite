<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->unsignedBigInteger('duplicate_of_media_id')->nullable()->after('content_hash');
            $table->index(['garage_id', 'duplicate_of_media_id']);
        });
    }

    public function down(): void
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->dropIndex(['garage_id', 'duplicate_of_media_id']);
            $table->dropColumn('duplicate_of_media_id');
        });
    }
};