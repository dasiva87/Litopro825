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
        Schema::create('supplier_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_company_id')
                  ->constrained('companies')
                  ->onDelete('cascade')
                  ->comment('Litografía cliente');
            $table->foreignId('supplier_company_id')
                  ->constrained('companies')
                  ->onDelete('cascade')
                  ->comment('Papelería proveedor');
            $table->foreignId('approved_by_user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('Usuario que aprobó la relación');
            $table->timestamp('approved_at')->useCurrent()->comment('Fecha de aprobación');
            $table->boolean('is_active')->default(true)->comment('Relación activa');
            $table->text('notes')->nullable()->comment('Notas adicionales');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['client_company_id', 'is_active']);
            $table->index(['supplier_company_id', 'is_active']);

            // Evitar relaciones duplicadas
            $table->unique(['client_company_id', 'supplier_company_id'], 'supplier_relationships_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_relationships');
    }
};
