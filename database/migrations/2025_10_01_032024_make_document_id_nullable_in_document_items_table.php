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
            // Primero eliminar la foreign key constraint
            $table->dropForeign(['document_id']);

            // Hacer la columna nullable
            $table->foreignId('document_id')->nullable()->change();

            // Recrear la foreign key con onDelete set null en lugar de cascade
            $table->foreign('document_id')
                  ->references('id')
                  ->on('documents')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            // Eliminar la foreign key
            $table->dropForeign(['document_id']);

            // Hacer la columna NOT NULL de nuevo
            $table->foreignId('document_id')->nullable(false)->change();

            // Recrear la foreign key original con cascade
            $table->foreign('document_id')
                  ->references('id')
                  ->on('documents')
                  ->cascadeOnDelete();
        });
    }
};
