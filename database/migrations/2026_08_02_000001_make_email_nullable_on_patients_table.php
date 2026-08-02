<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patients') || ! Schema::hasColumn('patients', 'email')) {
            return;
        }

        DB::statement('ALTER TABLE patients MODIFY email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('patients') || ! Schema::hasColumn('patients', 'email')) {
            return;
        }

        DB::statement('ALTER TABLE patients MODIFY email VARCHAR(255) NOT NULL');
    }
};
