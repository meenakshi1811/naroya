<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addClinicId = ! Schema::hasColumn('appointment', 'clinic_id');
        $addTokenNo = ! Schema::hasColumn('appointment', 'token_no');

        Schema::table('appointment', function (Blueprint $table) use ($addClinicId, $addTokenNo) {
            if ($addClinicId) {
                $table->unsignedBigInteger('clinic_id')->nullable()->after('dr_id');
                // Tokens are numbered per clinic per appointment date.
                $table->index(['clinic_id', 'varAppointment']);
            }

            if ($addTokenNo) {
                $table->unsignedInteger('token_no')->nullable()->after('clinic_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            if (Schema::hasColumn('appointment', 'token_no')) {
                $table->dropColumn('token_no');
            }

            if (Schema::hasColumn('appointment', 'clinic_id')) {
                $table->dropIndex(['clinic_id', 'varAppointment']);
                $table->dropColumn('clinic_id');
            }
        });
    }
};
