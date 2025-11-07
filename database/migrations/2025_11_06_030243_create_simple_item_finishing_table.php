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
        Schema::create('simple_item_finishing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simple_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finishing_id')->constrained()->cascadeOnDelete();

            // Parámetros según el tipo de medición del acabado
            $table->decimal('quantity', 10, 2)->nullable(); // Para MILLAR, RANGO, UNIDAD
            $table->decimal('width', 10, 2)->nullable();    // Para TAMAÑO
            $table->decimal('height', 10, 2)->nullable();   // Para TAMAÑO

            // Costo calculado (snapshot del cálculo)
            $table->decimal('calculated_cost', 10, 2)->default(0);

            // Indica si es un acabado sugerido por defecto
            $table->boolean('is_default')->default(true);

            // Orden de aplicación (para acabados que dependen de otros)
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Índices
            $table->index(['simple_item_id', 'finishing_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simple_item_finishing');
    }
};
