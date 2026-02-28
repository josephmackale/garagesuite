<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->unsignedInteger('pack_version')->nullable()->after('submitted_by');
            $table->string('pack_path', 500)->nullable()->after('pack_version');
            $table->string('pack_last_filename', 255)->nullable()->after('pack_path');
            $table->timestamp('pack_generated_at')->nullable()->after('pack_last_filename');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->dropColumn(['pack_version', 'pack_path', 'pack_last_filename', 'pack_generated_at']);
        });
    }
};