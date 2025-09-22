<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'draft'])->default('draft');

            // Configuración del trigger
            $table->string('event_type'); // subscription_created, payment_failed, user_signup, etc.
            $table->json('conditions'); // Condiciones que deben cumplirse
            $table->json('filters')->nullable(); // Filtros adicionales (company, plan, etc.)

            // Configuración de destinatarios
            $table->json('recipients'); // Lista de destinatarios
            $table->enum('recipient_type', ['static', 'dynamic', 'role_based'])->default('static');
            $table->json('recipient_rules')->nullable(); // Reglas para destinatarios dinámicos

            // Configuración de canales
            $table->json('channels'); // IDs de canales a usar
            $table->json('channel_priorities')->nullable(); // Prioridades por canal
            $table->boolean('require_all_channels')->default(false); // Si debe enviar por todos los canales

            // Configuración de timing
            $table->enum('delivery_timing', ['immediate', 'delayed', 'scheduled', 'business_hours'])->default('immediate');
            $table->integer('delay_minutes')->nullable(); // Para delivery_timing = delayed
            $table->json('schedule_config')->nullable(); // Para delivery_timing = scheduled
            $table->json('business_hours_config')->nullable(); // Para delivery_timing = business_hours

            // Configuración de frecuencia
            $table->boolean('rate_limit_enabled')->default(false);
            $table->integer('max_per_hour')->nullable();
            $table->integer('max_per_day')->nullable();
            $table->boolean('deduplicate')->default(true); // Evitar duplicados
            $table->integer('dedupe_window_minutes')->default(60);

            // Configuración de contenido
            $table->string('template')->nullable(); // Template a usar
            $table->json('template_variables')->nullable(); // Variables del template
            $table->text('custom_message')->nullable(); // Mensaje personalizado
            $table->json('attachments')->nullable(); // Archivos adjuntos

            // Configuración de escalation
            $table->boolean('escalation_enabled')->default(false);
            $table->json('escalation_rules')->nullable(); // Reglas de escalación
            $table->integer('escalation_delay_minutes')->default(30);

            // Configuración de alertas críticas
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->boolean('bypass_quiet_hours')->default(false);
            $table->boolean('require_acknowledgment')->default(false);

            // Métricas y monitoreo
            $table->integer('total_triggered')->default(0);
            $table->integer('total_sent')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('total_failed')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->decimal('success_rate', 5, 2)->nullable();

            // Metadatos
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->json('tags')->nullable(); // Tags para organización

            $table->timestamps();

            // Índices
            $table->index(['event_type', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['created_by', 'status']);
            $table->index(['delivery_timing', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
