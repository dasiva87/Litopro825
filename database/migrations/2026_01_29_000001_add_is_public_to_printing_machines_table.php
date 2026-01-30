<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el campo is_public a printing_machines.
 *
 * Este campo faltaba - la migración 2026_01_24 asumía incorrectamente
 * que ya existía pero nunca fue creado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printing_machines', function (Blueprint $table) {
            if (!Schema::hasColumn('printing_machines', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('is_active')
                    ->comment('Si es true, litografías clientes pueden ver esta máquina');
            }
        });
    }

    public function down(): void
    {
        Schema::table('printing_machines', function (Blueprint $table) {
            if (Schema::hasColumn('printing_machines', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }
};
