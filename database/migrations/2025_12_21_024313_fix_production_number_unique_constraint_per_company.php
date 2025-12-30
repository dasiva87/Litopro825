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
        Schema::table('production_orders', function (Blueprint $table) {
            // Eliminar índice único global
            $table->dropUnique(['production_number']);

            // Agregar índice único compuesto por company_id + production_number
            $table->unique(['company_id', 'production_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // Revertir: eliminar índice compuesto
            $table->dropUnique(['company_id', 'production_number']);

            // Restaurar índice único global
            $table->unique('production_number');
        });
    }
};
