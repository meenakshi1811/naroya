<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'bio_handle')) {
            DB::statement('ALTER TABLE `users` MODIFY `bio_handle` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'bio_handle')) {
            DB::statement('ALTER TABLE `users` MODIFY `bio_handle` VARCHAR(255) NULL');
        }
    }
};
