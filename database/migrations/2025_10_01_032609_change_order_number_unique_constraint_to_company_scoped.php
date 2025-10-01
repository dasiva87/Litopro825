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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Eliminar el constraint unique global
            $table->dropUnique(['order_number']);

            // Crear unique constraint compuesto (order_number + company_id)
            $table->unique(['order_number', 'company_id'], 'purchase_orders_order_number_company_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Eliminar el constraint compuesto
            $table->dropUnique('purchase_orders_order_number_company_unique');

            // Restaurar el constraint unique global
            $table->unique('order_number');
        });
    }
};
