<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clinic_token_queues')) {
            // One row per clinic per day holding the token currently being served.
            Schema::create('clinic_token_queues', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('clinic_id');
                $table->date('queue_date');
                $table->unsignedInteger('current_token')->default(0);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['clinic_id', 'queue_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_token_queues');
    }
};
