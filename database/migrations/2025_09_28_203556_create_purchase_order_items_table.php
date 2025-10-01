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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('document_item_id')->constrained()->onDelete('cascade');

            $table->enum('item_type', ['papel', 'producto']);
            $table->decimal('quantity_ordered', 10, 2);
            $table->decimal('unit_price', 10, 4);
            $table->decimal('total_price', 12, 2);

            // Campos específicos para papel (items sencillos)
            $table->integer('paper_sheets_needed')->nullable();
            $table->string('paper_cut_size')->nullable(); // Ej: "70x50cm"
            $table->string('paper_type')->nullable(); // Nombre del papel

            // Campos específicos para productos
            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable();

            $table->enum('status', ['pending', 'confirmed', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['purchase_order_id', 'status']);
            $table->index(['document_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
