<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * /patient/send-request used to give a clinic token to any booking with a doctor
     * who works at a clinic. Those are bookings with the doctor themself, not at the
     * clinic, so take them out of the clinic's token queue and free their numbers.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('appointment', 'is_clinic_booking')) {
            return;
        }

        DB::table('appointment')
            ->where('is_clinic_booking', 0)
            ->where(fn ($query) => $query->whereNotNull('clinic_id')->orWhereNotNull('token_no'))
            ->update([
                'clinic_id' => null,
                'token_no' => null,
                'token_reminder_sent_at' => null,
            ]);
    }

    /** The freed token numbers may have been booked again, so this can't be undone. */
    public function down(): void
    {
    }
};
