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
        Schema::table('finishings', function (Blueprint $table) {
            // Modificar el enum para agregar nuevos valores para talonarios
            $table->enum('measurement_unit', [
                'millar',
                'rango', 
                'unidad',
                'tamaño',
                // Nuevos para talonarios
                'por_numero',     // Para numeración (costo × cantidad de números)
                'por_talonario'   // Para perforación, engomado, armado (costo × cantidad de talonarios)
            ])->default('unidad')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finishings', function (Blueprint $table) {
            // Revertir a los valores originales
            $table->enum('measurement_unit', [
                'millar',
                'rango',
                'unidad',
                'tamaño'
            ])->default('unidad')->change();
        });
    }
};