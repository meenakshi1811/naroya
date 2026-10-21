<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Existing rows are all doctors, so the default backfills them.
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('doctor')->index();
            }

            if (! Schema::hasColumn('users', 'clinic_id')) {
                $table->unsignedBigInteger('clinic_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'clinic_id')) {
                $table->dropIndex(['clinic_id']);
                $table->dropColumn('clinic_id');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropIndex(['role']);
                $table->dropColumn('role');
            }
        });
    }
};
