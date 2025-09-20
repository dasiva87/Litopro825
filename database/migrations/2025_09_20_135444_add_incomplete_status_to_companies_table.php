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
        // Agregar 'incomplete' al ENUM de status y cambiar default
        DB::statement("ALTER TABLE companies MODIFY COLUMN status ENUM('active', 'suspended', 'cancelled', 'trial', 'pending', 'incomplete') DEFAULT 'incomplete'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el ENUM y default al estado anterior
        DB::statement("ALTER TABLE companies MODIFY COLUMN status ENUM('active', 'suspended', 'cancelled', 'trial', 'pending') DEFAULT 'pending'");
    }
};
