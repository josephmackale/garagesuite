<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add a generated column that is NON-NULL only for draft rows
        DB::statement("
            ALTER TABLE job_inspections
            ADD COLUMN draft_guard VARCHAR(64)
            GENERATED ALWAYS AS (
                CASE
                    WHEN status = 'draft' AND job_id IS NOT NULL AND garage_id IS NOT NULL
                    THEN CONCAT(garage_id, '-', job_id)
                    ELSE NULL
                END
            ) STORED
        ");

        // 2) Enforce: only one draft inspection per (garage_id, job_id)
        DB::statement("
            CREATE UNIQUE INDEX job_inspections_draft_guard_unique
            ON job_inspections (draft_guard)
        ");
    }

    public function down(): void
    {
        // Drop index first, then the column
        DB::statement("DROP INDEX job_inspections_draft_guard_unique ON job_inspections");
        DB::statement("ALTER TABLE job_inspections DROP COLUMN draft_guard");
    }
};

