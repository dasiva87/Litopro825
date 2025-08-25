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
        Schema::create('simple_items', function (Blueprint $table) {
            $table->id();
            
            // Información básica del item
            $table->text('description'); // Descripción del trabajo
            $table->decimal('quantity', 10, 2)->default(1.00); // Cantidad solicitada
            
            // Dimensiones del producto final
            $table->decimal('horizontal_size', 8, 2); // Tamaño horizontal (ancho)
            $table->decimal('vertical_size', 8, 2); // Tamaño vertical (alto)
            
            // Campos calculados automáticamente
            $table->integer('mounting_quantity')->default(0); // Cantidad de montaje calculado
            $table->decimal('paper_cuts_h', 8, 2)->default(0); // Cortes horizontales calculados
            $table->decimal('paper_cuts_v', 8, 2)->default(0); // Cortes verticales calculados
            
            // Información de tintas
            $table->integer('ink_front_count')->default(0); // Número de tintas tiro
            $table->integer('ink_back_count')->default(0); // Número de tintas retiro
            $table->boolean('front_back_plate')->default(false); // Tiro y retiro plancha
            
            // Costos adicionales
            $table->decimal('design_value', 10, 2)->default(0.00); // Valor diseño
            $table->decimal('transport_value', 10, 2)->default(0.00); // Valor transporte
            $table->decimal('rifle_value', 10, 2)->default(0.00); // Valor rifle/doblez
            $table->decimal('profit_percentage', 5, 2)->default(0.00); // Porcentaje ganancia
            
            // Relaciones con catálogos
            $table->foreignId('paper_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('printing_machine_id')->nullable()->constrained()->nullOnDelete();
            
            // Costos calculados (se pueden cachear para rendimiento)
            $table->decimal('paper_cost', 10, 2)->default(0.00); // Costo del papel
            $table->decimal('printing_cost', 10, 2)->default(0.00); // Costo de impresión
            $table->decimal('mounting_cost', 10, 2)->default(0.00); // Costo de montaje
            $table->decimal('total_cost', 10, 2)->default(0.00); // Costo total sin ganancia
            $table->decimal('final_price', 10, 2)->default(0.00); // Precio final con ganancia
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para consultas frecuentes
            $table->index(['paper_id', 'printing_machine_id']);
            $table->index(['horizontal_size', 'vertical_size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simple_items');
    }
};
