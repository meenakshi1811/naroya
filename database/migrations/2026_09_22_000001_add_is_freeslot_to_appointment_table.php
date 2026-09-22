<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            if (! Schema::hasColumn('appointment', 'is_freeslot')) {
                $table->unsignedTinyInteger('is_freeslot')->default(0)->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            if (Schema::hasColumn('appointment', 'is_freeslot')) {
                $table->dropColumn('is_freeslot');
            }
        });
    }
};
