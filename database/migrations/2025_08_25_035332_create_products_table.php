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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->nullable(); // Código interno del producto
            $table->decimal('purchase_price', 12, 2)->default(0); // Precio de compra
            $table->decimal('sale_price', 12, 2); // Precio de venta
            $table->boolean('is_own_product')->default(true); // Si es producto propio o de terceros
            $table->foreignId('supplier_contact_id')->nullable()->constrained('contacts')->nullOnDelete(); // Proveedor si no es propio
            $table->integer('stock')->default(0); // Stock disponible
            $table->integer('min_stock')->default(0); // Stock mínimo
            $table->boolean('active')->default(true); // Producto activo
            $table->json('metadata')->nullable(); // Para campos adicionales
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'active']);
            $table->index(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};