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
        Schema::table('simple_items', function (Blueprint $table) {
            // Campos para montaje personalizado
            $table->decimal('custom_paper_width', 10, 2)->nullable()->after('mounting_quantity');
            $table->decimal('custom_paper_height', 10, 2)->nullable()->after('custom_paper_width');

            // Tipo de montaje: 'automatic' (máquina) o 'custom' (manual)
            $table->enum('mounting_type', ['automatic', 'custom'])->default('automatic')->after('custom_paper_height');

            // Datos calculados del montaje custom (para guardar el resultado)
            $table->json('custom_mounting_data')->nullable()->after('mounting_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simple_items', function (Blueprint $table) {
            $table->dropColumn(['custom_paper_width', 'custom_paper_height', 'mounting_type', 'custom_mounting_data']);
        });
    }
};
