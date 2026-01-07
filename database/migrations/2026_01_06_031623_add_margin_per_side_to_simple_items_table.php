<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agregar campo configurable para el margen del montaje
     * Por defecto 1cm, pero el usuario puede ajustarlo según las necesidades del trabajo
     */
    public function up(): void
    {
        Schema::table('simple_items', function (Blueprint $table) {
            // Margen por lado en centímetros (por defecto 1cm)
            $table->decimal('margin_per_side', 5, 2)->default(1.00)->after('copies_per_form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simple_items', function (Blueprint $table) {
            $table->dropColumn('margin_per_side');
        });
    }
};
