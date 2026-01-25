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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name')->nullable();
            $table->string('owner_phone');
            $table->string('car_name')->nullable();
            $table->string('car_number');
            $table->string('car_letter');
            $table->string('car_image')->nullable();
            $table->longText('car_description')->nullable();
            
            $table->unique(['car_number', 'car_letter']);
            $table->index(['owner_phone', 'car_number', 'car_letter']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
