<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar el ENUM para incluir los nuevos estados: received, on_hold
        DB::statement("ALTER TABLE production_orders MODIFY COLUMN status ENUM('draft', 'sent', 'received', 'in_progress', 'on_hold', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primero actualizar registros con estados nuevos a estados anteriores
        DB::table('production_orders')
            ->where('status', 'received')
            ->update(['status' => 'sent']);

        DB::table('production_orders')
            ->where('status', 'on_hold')
            ->update(['status' => 'in_progress']);

        // Revertir el ENUM a los valores originales
        DB::statement("ALTER TABLE production_orders MODIFY COLUMN status ENUM('draft', 'sent', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }
};
