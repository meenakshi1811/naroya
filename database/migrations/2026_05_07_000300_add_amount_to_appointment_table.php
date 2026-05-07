<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            if (!Schema::hasColumn('appointment', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable()->after('dr_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            if (Schema::hasColumn('appointment', 'amount')) {
                $table->dropColumn('amount');
            }
        });
    }
};
