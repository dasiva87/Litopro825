<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el ENUM para incluir 'pending'
        DB::statement("ALTER TABLE companies MODIFY COLUMN status ENUM('active', 'suspended', 'cancelled', 'trial', 'pending') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el ENUM a su estado anterior
        DB::statement("ALTER TABLE companies MODIFY COLUMN status ENUM('active', 'suspended', 'cancelled', 'trial') DEFAULT 'active'");
    }
};
