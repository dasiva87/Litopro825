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
        // Agregar 'incomplete' al ENUM de status y cambiar default - Compatible con SQLite y MySQL
        Schema::table('companies', function (Blueprint $table) {
            $table->string('status')->default('incomplete')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el ENUM y default al estado anterior
        Schema::table('companies', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};
