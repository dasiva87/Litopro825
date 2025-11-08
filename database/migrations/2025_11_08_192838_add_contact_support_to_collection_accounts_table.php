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
        Schema::table('collection_accounts', function (Blueprint $table) {
            // Agregar soporte para contactos
            $table->foreignId('contact_id')->nullable()->after('client_company_id')->constrained('contacts')->nullOnDelete();

            // Hacer client_company_id nullable ya que ahora puede ser company O contact
            $table->foreignId('client_company_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_accounts', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');

            // Revertir client_company_id a NOT NULL
            $table->foreignId('client_company_id')->nullable(false)->change();
        });
    }
};
