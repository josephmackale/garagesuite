<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->index();
            $table->unique(['garage_id', 'content_hash'], 'media_items_garage_hash_unique');
        });
    }

    public function down()
    {
        Schema::table('media_items', function (Blueprint $table) {
            $table->dropUnique('media_items_garage_hash_unique');
            $table->dropColumn('content_hash');
        });
    }
};
