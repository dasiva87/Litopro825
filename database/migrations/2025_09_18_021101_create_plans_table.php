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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Básico, Pro, Enterprise
            $table->string('slug')->unique(); // basico, pro, enterprise
            $table->text('description')->nullable();
            $table->string('stripe_price_id'); // Price ID de Stripe
            $table->decimal('price', 8, 2); // Precio mensual
            $table->string('currency', 3)->default('usd');
            $table->string('interval')->default('month'); // month, year
            $table->json('features'); // Lista de características
            $table->json('limits'); // Límites del plan
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
