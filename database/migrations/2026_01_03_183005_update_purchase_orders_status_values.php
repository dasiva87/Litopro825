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
        // Paso 1: Modificar el ENUM para incluir 'in_progress' y eliminar 'confirmed', 'partially_received', 'received'
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft', 'sent', 'confirmed', 'in_progress', 'partially_received', 'received', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");

        // Paso 2: Actualizar estados antiguos a nuevos valores
        DB::statement("UPDATE purchase_orders SET status = 'in_progress' WHERE status = 'confirmed'");
        DB::statement("UPDATE purchase_orders SET status = 'completed' WHERE status = 'received' OR status = 'partially_received'");

        // Paso 3: Limpiar el ENUM dejando solo los estados nuevos
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft', 'sent', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Paso 1: Modificar el ENUM para incluir estados antiguos
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft', 'sent', 'confirmed', 'in_progress', 'partially_received', 'received', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");

        // Paso 2: Revertir a estados antiguos
        DB::statement("UPDATE purchase_orders SET status = 'confirmed' WHERE status = 'in_progress'");
        DB::statement("UPDATE purchase_orders SET status = 'received' WHERE status = 'completed'");

        // Paso 3: Limpiar el ENUM dejando solo los estados antiguos
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft', 'sent', 'confirmed', 'partially_received', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }
};
