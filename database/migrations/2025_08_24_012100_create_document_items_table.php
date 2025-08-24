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
        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            
            // Relación principal con documento
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            
            // Relaciones con catálogos (opcionales para diferentes tipos de items)
            $table->foreignId('printing_machine_id')->nullable()->constrained();
            $table->foreignId('paper_id')->nullable()->constrained();
            
            // Información básica del item
            $table->text('description'); // Descripción del trabajo
            $table->decimal('quantity', 10, 2)->default(1.00); // Cantidad solicitada
            
            // Dimensiones del producto final
            $table->decimal('width', 8, 2)->nullable(); // Ancho del producto final
            $table->decimal('height', 8, 2)->nullable(); // Alto del producto final
            $table->integer('pages')->default(1); // Páginas (para revistas, catálogos)
            
            // Información de tintas
            $table->integer('colors_front')->default(0); // Colores frente (4x0, 1x1, etc.)
            $table->integer('colors_back')->default(0); // Colores reverso
            
            // Dimensiones de corte de papel (calculadora de cortes)
            $table->decimal('paper_cut_width', 8, 2)->nullable(); // Ancho de corte en pliego
            $table->decimal('paper_cut_height', 8, 2)->nullable(); // Alto de corte en pliego
            $table->string('orientation')->default('horizontal'); // Orientación del corte
            $table->integer('cuts_per_sheet')->default(1); // Cortes por pliego (calculado)
            $table->integer('sheets_needed')->default(1); // Pliegos necesarios (calculado)
            
            // Unidades y aprovechamiento
            $table->integer('unit_copies')->default(1); // Copias por unidad (talonarios)
            
            // Costos desglosados
            $table->decimal('paper_cost', 10, 2)->default(0.00); // Costo del papel
            $table->decimal('printing_cost', 10, 2)->default(0.00); // Costo de impresión
            $table->decimal('cutting_cost', 10, 2)->default(0.00); // Costo de corte/guillotina
            $table->decimal('design_cost', 10, 2)->default(0.00); // Costo de diseño
            $table->decimal('transport_cost', 10, 2)->default(0.00); // Costo de transporte
            $table->decimal('other_costs', 10, 2)->default(0.00); // Otros costos
            
            // Pricing
            $table->decimal('unit_price', 10, 2)->default(0.00); // Precio unitario
            $table->decimal('total_price', 12, 2)->default(0.00); // Total del item
            $table->decimal('profit_margin', 5, 2)->default(0.00); // Margen de ganancia %
            
            // Tipo de item y configuración
            $table->enum('item_type', [
                'simple', 'talonario', 'magazine', 'digital', 'custom', 'product'
            ])->default('simple');
            $table->json('item_config')->nullable(); // Configuración específica del tipo
            
            // Templates
            $table->boolean('is_template')->default(false); // Para reutilizar items
            $table->string('template_name')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['document_id', 'item_type']);
            $table->index(['document_id', 'is_template']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};
