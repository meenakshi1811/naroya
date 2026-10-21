<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clinics')) {
            Schema::create('clinics', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('registration_no', 100)->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('website')->nullable();
                $table->string('address', 500)->nullable();
                $table->unsignedInteger('country')->nullable();
                $table->unsignedInteger('state')->nullable();
                $table->unsignedInteger('city_id')->nullable()->index();
                $table->string('postal_code', 20)->nullable();
                $table->text('description')->nullable();
                $table->string('logo')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
