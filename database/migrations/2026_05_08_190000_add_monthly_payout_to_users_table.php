<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'monthly_payout')) {
                $table->unsignedTinyInteger('monthly_payout')->default(0)->after('chrApproval');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'monthly_payout')) {
                $table->dropColumn('monthly_payout');
            }
        });
    }
};
