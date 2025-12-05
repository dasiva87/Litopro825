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
        Schema::table('contacts', function (Blueprint $table) {
            // Campos para diferenciar contactos locales vs Grafired
            $table->boolean('is_local')->default(true)->after('is_active');
            $table->unsignedBigInteger('linked_company_id')->nullable()->after('is_local');
            
            // Índices para optimización
            $table->index(['company_id', 'is_local', 'type'], 'contacts_company_local_type_index');
            $table->index(['linked_company_id'], 'contacts_linked_company_index');
            
            // Foreign key para empresa vinculada
            $table->foreign('linked_company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['linked_company_id']);
            $table->dropIndex('contacts_company_local_type_index');
            $table->dropIndex('contacts_linked_company_index');
            $table->dropColumn(['is_local', 'linked_company_id']);
        });
    }
};