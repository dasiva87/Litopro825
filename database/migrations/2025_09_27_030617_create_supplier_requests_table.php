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
        Schema::create('supplier_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_company_id')
                  ->constrained('companies')
                  ->onDelete('cascade')
                  ->comment('Litografía que solicita');
            $table->foreignId('supplier_company_id')
                  ->constrained('companies')
                  ->onDelete('cascade')
                  ->comment('Papelería solicitada como proveedor');
            $table->foreignId('requested_by_user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('Usuario que hizo la solicitud');
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->comment('Estado de la solicitud');
            $table->text('message')->nullable()->comment('Mensaje de la solicitud');
            $table->text('response_message')->nullable()->comment('Mensaje de respuesta');
            $table->timestamp('responded_at')->nullable()->comment('Fecha de respuesta');
            $table->foreignId('responded_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Usuario que respondió');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['requester_company_id', 'status']);
            $table->index(['supplier_company_id', 'status']);

            // Evitar solicitudes duplicadas
            $table->unique(['requester_company_id', 'supplier_company_id'], 'supplier_requests_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_requests');
    }
};
