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
        // Primero eliminar índice único compuesto
        Schema::table('digital_items', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
        });

        // Luego eliminar columnas
        Schema::table('digital_items', function (Blueprint $table) {
            $table->dropColumn('code');
            $table->dropColumn('purchase_price');
            $table->dropColumn('profit_margin');
            $table->dropColumn('unit_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digital_items', function (Blueprint $table) {
            // Restaurar columnas si es necesario hacer rollback
            $table->string('code')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0.00);
            $table->decimal('profit_margin', 5, 2)->default(0.00);
            $table->decimal('unit_value', 10, 2)->nullable();

            // Restaurar índice
            $table->index(['code']);
        });
    }
};
