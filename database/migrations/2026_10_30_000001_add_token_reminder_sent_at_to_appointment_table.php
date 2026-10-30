<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            // Set once the patient has been told their clinic token is coming up,
            // so moving the running token back and forth never repeats the reminder.
            if (! Schema::hasColumn('appointment', 'token_reminder_sent_at')) {
                $table->timestamp('token_reminder_sent_at')->nullable()->after('is_present');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            if (Schema::hasColumn('appointment', 'token_reminder_sent_at')) {
                $table->dropColumn('token_reminder_sent_at');
            }
        });
    }
};
