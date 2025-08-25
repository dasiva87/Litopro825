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
        Schema::table('document_items', function (Blueprint $table) {
            // Agregar campos polimórficos
            $table->string('itemable_type')->nullable()->after('document_id');
            $table->unsignedBigInteger('itemable_id')->nullable()->after('itemable_type');
            
            // Crear índice compuesto para la relación polimórfica
            $table->index(['itemable_type', 'itemable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            $table->dropIndex(['itemable_type', 'itemable_id']);
            $table->dropColumn(['itemable_type', 'itemable_id']);
        });
    }
};
