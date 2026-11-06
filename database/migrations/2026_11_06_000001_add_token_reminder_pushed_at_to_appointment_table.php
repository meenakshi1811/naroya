<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            // token_reminder_sent_at marks a clinic token as due its "your turn is coming
            // up" push; this is set once the clinic:token-reminders job has handled it.
            if (! Schema::hasColumn('appointment', 'token_reminder_pushed_at')) {
                $table->timestamp('token_reminder_pushed_at')->nullable()->after('token_reminder_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            if (Schema::hasColumn('appointment', 'token_reminder_pushed_at')) {
                $table->dropColumn('token_reminder_pushed_at');
            }
        });
    }
};
