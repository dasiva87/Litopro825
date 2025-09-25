<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            // Agregar company_id como foreign key
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Crear índice compuesto para mejor performance
            $table->index(['company_id', 'document_id'], 'document_items_company_document_index');
        });

        // Poblar company_id existentes basado en la tabla documents
        DB::statement('
            UPDATE document_items di
            JOIN documents d ON di.document_id = d.id
            SET di.company_id = d.company_id
            WHERE di.company_id IS NULL
        ');

        // Hacer company_id requerido después de poblar datos
        Schema::table('document_items', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            $table->dropIndex('document_items_company_document_index');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
