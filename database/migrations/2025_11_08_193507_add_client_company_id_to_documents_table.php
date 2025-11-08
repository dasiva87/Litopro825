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
        Schema::table('documents', function (Blueprint $table) {
            // Agregar soporte para empresas conectadas como clientes
            $table->foreignId('client_company_id')->nullable()->after('contact_id')->constrained('companies')->nullOnDelete();

            // Hacer contact_id nullable ya que ahora puede ser company O contact
            $table->foreignId('contact_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['client_company_id']);
            $table->dropColumn('client_company_id');

            // Revertir contact_id a NOT NULL si es necesario
            // $table->foreignId('contact_id')->nullable(false)->change();
        });
    }
};
