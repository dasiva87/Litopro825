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
        Schema::create('digital_items', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // Información básica del producto digital
            $table->text('description'); // Descripción del servicio

            // Precio
            $table->decimal('sale_price', 10, 2); // Precio de venta

            // Información del proveedor
            $table->boolean('is_own_product')->default(true); // Si es propio o de terceros
            $table->foreignId('supplier_contact_id')->nullable()->constrained('contacts')->nullOnDelete();

            // Tipo de valoración
            $table->enum('pricing_type', ['unit', 'size'])->default('unit'); // Por unidad o por tamaño
            
            // Datos adicionales
            $table->json('metadata')->nullable(); // Información adicional
            $table->boolean('active')->default(true); // Estado activo/inactivo
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['company_id', 'active']);
            $table->index(['pricing_type']);
            $table->index(['is_own_product']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_items');
    }
};