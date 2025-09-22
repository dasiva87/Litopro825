<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');

            // Cliente enterprise específico
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Plan base del cual se deriva
            $table->foreignId('base_plan_id')->constrained('plans')->onDelete('cascade');

            // Configuración de precios personalizada
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->string('custom_interval')->nullable(); // month, year, custom
            $table->integer('custom_interval_count')->default(1); // Cada X meses/años
            $table->decimal('discount_percentage', 5, 2)->default(0); // Descuento adicional

            // Límites personalizados
            $table->json('custom_limits')->nullable(); // Override de límites del plan base
            $table->json('additional_features')->nullable(); // Features exclusivas para este cliente
            $table->json('removed_features')->nullable(); // Features del plan base que no aplican

            // Configuración de facturación personalizada
            $table->boolean('custom_billing_cycle')->default(false);
            $table->integer('billing_day')->nullable(); // Día específico del mes para facturar
            $table->string('payment_terms')->nullable(); // NET30, NET60, etc.
            $table->boolean('requires_po')->default(false); // Requiere Purchase Order
            $table->text('billing_notes')->nullable();

            // SLA y soporte
            $table->json('sla_terms')->nullable(); // Términos de SLA específicos
            $table->string('support_tier')->nullable(); // basic, premium, enterprise, white-glove
            $table->boolean('dedicated_support')->default(false);
            $table->string('account_manager_email')->nullable();

            // Configuración técnica personalizada
            $table->integer('api_rate_limit')->nullable(); // Límite personalizado de API calls
            $table->boolean('white_labeling')->default(false);
            $table->json('custom_integrations')->nullable(); // Integraciones específicas
            $table->boolean('single_sign_on')->default(false);
            $table->json('security_requirements')->nullable(); // Requerimientos de seguridad específicos

            // Dates y períodos
            $table->timestamp('effective_date')->nullable(); // Cuándo entra en vigor
            $table->timestamp('expiration_date')->nullable(); // Cuándo expira (renovación manual)
            $table->integer('contract_length_months')->nullable(); // Duración del contrato
            $table->boolean('auto_renewal')->default(false);

            // Aprobaciones y workflow
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Metadatos
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('internal_notes')->nullable();
            $table->json('contract_documents')->nullable(); // URLs a documentos del contrato

            $table->timestamps();

            // Índices
            $table->index(['company_id', 'status']);
            $table->index(['base_plan_id', 'status']);
            $table->index(['approval_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_plans');
    }
};
