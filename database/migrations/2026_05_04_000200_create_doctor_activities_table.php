<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('activity_type', 100);
            $table->text('description');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_activities');
    }
};
