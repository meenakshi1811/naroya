<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_UNIQUE = 'clinic_token_queues_clinic_id_queue_date_unique';
    private const NEW_UNIQUE = 'clinic_token_queues_clinic_doctor_date_unique';

    public function up(): void
    {
        // Tokens are numbered per doctor within a clinic, so each doctor has their
        // own running token: one queue row per clinic, doctor and day.
        if (! Schema::hasColumn('clinic_token_queues', 'doctor_id')) {
            Schema::table('clinic_token_queues', function (Blueprint $table) {
                $table->unsignedBigInteger('doctor_id')->nullable()->after('clinic_id');
            });
        }

        $indexes = collect(Schema::getIndexes('clinic_token_queues'))->pluck('name');

        Schema::table('clinic_token_queues', function (Blueprint $table) use ($indexes) {
            if (! $indexes->contains(self::NEW_UNIQUE)) {
                $table->unique(['clinic_id', 'doctor_id', 'queue_date'], self::NEW_UNIQUE);
            }

            if ($indexes->contains(self::OLD_UNIQUE)) {
                $table->dropUnique(self::OLD_UNIQUE);
            }
        });

        // A clinic-wide row of a one-doctor clinic was that doctor's queue; keep its
        // running token. Rows of clinics with several doctors can't be split and are ignored.
        DB::table('clinic_token_queues')->whereNull('doctor_id')->orderBy('id')->get()->each(function ($queue) {
            $doctorIds = DB::table('users')
                ->where('clinic_id', $queue->clinic_id)
                ->where('role', 'doctor')
                ->pluck('id');

            if ($doctorIds->count() === 1) {
                DB::table('clinic_token_queues')->where('id', $queue->id)->update(['doctor_id' => $doctorIds->first()]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('clinic_token_queues', 'doctor_id')) {
            return;
        }

        // Back to one row per clinic per day: per-doctor rows can't be merged.
        DB::table('clinic_token_queues')->whereNotNull('doctor_id')->delete();

        Schema::table('clinic_token_queues', function (Blueprint $table) {
            $table->unique(['clinic_id', 'queue_date'], self::OLD_UNIQUE);
            $table->dropUnique(self::NEW_UNIQUE);
            $table->dropColumn('doctor_id');
        });
    }
};
