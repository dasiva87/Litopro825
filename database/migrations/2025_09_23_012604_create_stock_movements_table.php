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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Polymorphic relation to stockable items (Product, Paper, etc.)
            $table->morphs('stockable');

            // Movement details
            $table->enum('type', ['in', 'out', 'adjustment']); // Entrada, Salida, Ajuste
            $table->enum('reason', [
                'initial_stock',     // Stock inicial
                'purchase',          // Compra
                'sale',             // Venta
                'return',           // Devolución
                'damage',           // Daño/Pérdida
                'adjustment',       // Ajuste manual
                'production',       // Producción
                'transfer'          // Transferencia
            ]);

            // Quantities
            $table->integer('quantity'); // Cantidad del movimiento (+ o -)
            $table->integer('previous_stock'); // Stock anterior
            $table->integer('new_stock'); // Stock después del movimiento

            // Cost tracking
            $table->decimal('unit_cost', 12, 4)->nullable(); // Costo unitario
            $table->decimal('total_cost', 12, 2)->nullable(); // Costo total del movimiento

            // References
            $table->string('reference_type')->nullable(); // Tipo de documento de referencia
            $table->unsignedBigInteger('reference_id')->nullable(); // ID del documento de referencia
            $table->string('batch_number')->nullable(); // Número de lote

            // Metadata
            $table->text('notes')->nullable(); // Notas adicionales
            $table->json('metadata')->nullable(); // Datos adicionales

            // User tracking
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'stockable_type', 'stockable_id']);
            $table->index(['company_id', 'type', 'created_at']);
            $table->index(['company_id', 'reason', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
