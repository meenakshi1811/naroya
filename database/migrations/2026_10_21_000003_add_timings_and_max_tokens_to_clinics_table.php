<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            // Daily sessions, e.g. [{"start":"09:00","end":"13:00"},{"start":"16:00","end":"20:00"}].
            if (! Schema::hasColumn('clinics', 'timings')) {
                $table->json('timings')->nullable()->after('postal_code');
            }

            if (! Schema::hasColumn('clinics', 'max_tokens')) {
                $table->unsignedInteger('max_tokens')->nullable()->after('timings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            if (Schema::hasColumn('clinics', 'max_tokens')) {
                $table->dropColumn('max_tokens');
            }

            if (Schema::hasColumn('clinics', 'timings')) {
                $table->dropColumn('timings');
            }
        });
    }
};
