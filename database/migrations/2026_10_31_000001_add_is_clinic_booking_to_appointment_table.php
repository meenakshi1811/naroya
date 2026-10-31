<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('appointment', 'is_clinic_booking')) {
            return;
        }

        // 1 for a token booked at a clinic (/patient/send-request-clinic). Those aren't
        // time-slot consultations, so the doctor's request list and the slot lists skip them.
        Schema::table('appointment', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_clinic_booking')->default(0)->after('token_no');
        });

        // Existing clinic bookings: send-request-clinic saves no symptoms, which the
        // time-slot booking API always requires.
        DB::table('appointment')
            ->whereNotNull('clinic_id')
            ->where(fn ($query) => $query->whereNull('varSympton')->orWhere('varSympton', ''))
            ->update(['is_clinic_booking' => 1]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointment', 'is_clinic_booking')) {
            Schema::table('appointment', function (Blueprint $table) {
                $table->dropColumn('is_clinic_booking');
            });
        }
    }
};
