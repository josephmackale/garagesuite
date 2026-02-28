<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Drop FK if it exists
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'job_repair_items'
              AND COLUMN_NAME = 'assigned_to'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        if ($fk && !empty($fk->name)) {
            DB::statement("ALTER TABLE job_repair_items DROP FOREIGN KEY `{$fk->name}`");
        }

        // 2) Drop indexes if they exist (names may vary)
        $indexes = DB::select("
            SELECT INDEX_NAME AS name
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'job_repair_items'
              AND INDEX_NAME IN ('idx_repair_items_assigned', 'job_repair_items_assigned_technician_id_index')
        ");

        foreach ($indexes as $idx) {
            DB::statement("ALTER TABLE job_repair_items DROP INDEX `{$idx->name}`");
        }

        // 3) Drop columns only if they exist
        Schema::table('job_repair_items', function ($table) {
            // We cannot rely on Blueprint dropColumn if column doesn't exist,
            // so we'll run conditional drops via DB statements below.
        });

        $cols = DB::select("
            SELECT COLUMN_NAME AS name
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'job_repair_items'
              AND COLUMN_NAME IN ('assigned_to','assigned_technician_id','status','type')
        ");

        $colNames = array_map(fn($c) => $c->name, $cols);

        if (in_array('assigned_to', $colNames, true)) {
            DB::statement("ALTER TABLE job_repair_items DROP COLUMN assigned_to");
        }
        if (in_array('assigned_technician_id', $colNames, true)) {
            DB::statement("ALTER TABLE job_repair_items DROP COLUMN assigned_technician_id");
        }
        if (in_array('status', $colNames, true)) {
            DB::statement("ALTER TABLE job_repair_items DROP COLUMN status");
        }
        if (in_array('type', $colNames, true)) {
            DB::statement("ALTER TABLE job_repair_items DROP COLUMN type");
        }
    }


    public function down(): void
    {
        Schema::table('job_repair_items', function (Blueprint $table) {
            // Recreate columns (only if you ever rollback)
            $table->unsignedBigInteger('assigned_to')->nullable()->index('idx_repair_items_assigned');
            $table->unsignedBigInteger('assigned_technician_id')->nullable()->index('job_repair_items_assigned_technician_id_index');
            $table->string('status', 20)->nullable()->default('unknown');
            $table->string('type', 20)->nullable()->default('not_assigned');

            // Recreate FK
            $table->foreign('assigned_to', 'fk_job_repair_items_assigned_user')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }
};