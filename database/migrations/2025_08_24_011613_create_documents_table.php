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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            
            // Relaciones principales
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Quien crea el documento
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete(); // Cliente/Proveedor
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            
            // Información del documento
            $table->string('document_number'); // COT-2024-001, ORD-2024-001, etc.
            $table->string('reference')->nullable(); // Referencia externa del cliente
            $table->date('date'); // Fecha del documento
            $table->date('due_date')->nullable(); // Fecha de vencimiento/entrega
            
            // Estado del documento
            $table->enum('status', [
                'draft', 'sent', 'approved', 'rejected', 
                'in_production', 'completed', 'cancelled'
            ])->default('draft');
            
            // Montos financieros
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            
            // Campos adicionales
            $table->text('notes')->nullable(); // Notas para el cliente
            $table->text('internal_notes')->nullable(); // Notas internas
            $table->date('valid_until')->nullable(); // Válido hasta (cotizaciones)
            
            // Control de versiones
            $table->integer('version')->default(1);
            $table->foreignId('parent_document_id')->nullable()->constrained('documents');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para performance
            $table->index(['company_id', 'document_type_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'date']);
            $table->index(['contact_id', 'status']);
            $table->unique(['company_id', 'document_number']); // Numeración única por empresa
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
