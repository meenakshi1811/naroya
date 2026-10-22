<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            // Marked by clinic staff when the patient arrives for their token.
            if (! Schema::hasColumn('appointment', 'is_present')) {
                $table->unsignedTinyInteger('is_present')->default(0)->after('token_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            if (Schema::hasColumn('appointment', 'is_present')) {
                $table->dropColumn('is_present');
            }
        });
    }
};
