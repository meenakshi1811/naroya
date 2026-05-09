<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctor_bank_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->unique();
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->enum('account_type', ['Savings', 'Current']);
            $table->string('account_number', 50);
            $table->string('ifsc_code', 20);
            $table->timestamps();

            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_bank_details');
    }
};
