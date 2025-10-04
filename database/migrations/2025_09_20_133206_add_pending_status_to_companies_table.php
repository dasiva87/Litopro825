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
        // Modificar el ENUM para incluir 'pending' - Compatible con SQLite y MySQL
        Schema::table('companies', function (Blueprint $table) {
            // SQLite no soporta MODIFY COLUMN, así que usamos change()
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el ENUM a su estado anterior
        Schema::table('companies', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });
    }
};
