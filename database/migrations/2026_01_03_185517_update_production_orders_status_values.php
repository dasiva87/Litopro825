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
        // Paso 1: Modificar el ENUM para incluir 'sent' y mantener estados antiguos temporalmente
        DB::statement("ALTER TABLE production_orders MODIFY status ENUM('draft', 'sent', 'queued', 'in_progress', 'completed', 'cancelled', 'on_hold') NOT NULL DEFAULT 'draft'");

        // Paso 2: Actualizar estados antiguos a nuevos valores
        DB::statement("UPDATE production_orders SET status = 'sent' WHERE status = 'queued'");
        DB::statement("UPDATE production_orders SET status = 'completed' WHERE status = 'on_hold'");

        // Paso 3: Limpiar el ENUM dejando solo los estados nuevos
        DB::statement("ALTER TABLE production_orders MODIFY status ENUM('draft', 'sent', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Paso 1: Modificar el ENUM para incluir estados antiguos
        DB::statement("ALTER TABLE production_orders MODIFY status ENUM('draft', 'sent', 'queued', 'in_progress', 'completed', 'cancelled', 'on_hold') NOT NULL DEFAULT 'draft'");

        // Paso 2: Revertir a estados antiguos
        DB::statement("UPDATE production_orders SET status = 'queued' WHERE status = 'sent'");
        DB::statement("UPDATE production_orders SET status = 'on_hold' WHERE status = 'completed'");

        // Paso 3: Limpiar el ENUM dejando solo los estados antiguos
        DB::statement("ALTER TABLE production_orders MODIFY status ENUM('draft', 'queued', 'in_progress', 'completed', 'cancelled', 'on_hold') NOT NULL DEFAULT 'draft'");
    }
};
