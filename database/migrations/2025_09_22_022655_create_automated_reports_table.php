<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automated_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'draft'])->default('draft');

            // Configuración del reporte
            $table->string('report_type'); // financial, user_activity, subscription_metrics, etc.
            $table->json('data_sources'); // Qué datos incluir
            $table->json('metrics'); // Métricas específicas a calcular
            $table->json('filters')->nullable(); // Filtros aplicados
            $table->json('grouping')->nullable(); // Agrupación (por fecha, plan, región, etc.)

            // Configuración de scheduling
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'])->default('monthly');
            $table->string('custom_cron')->nullable(); // Para frecuencia custom
            $table->integer('day_of_month')->nullable(); // Para reportes mensuales (1-28)
            $table->integer('day_of_week')->nullable(); // Para reportes semanales (0-6, 0=domingo)
            $table->time('time_of_day')->default('09:00'); // Hora de envío
            $table->string('timezone')->default('UTC');

            // Configuración de entrega
            $table->json('recipients'); // Emails de destinatarios
            $table->json('delivery_methods'); // email, slack, teams, webhook, etc.
            $table->enum('format', ['pdf', 'excel', 'csv', 'html', 'json'])->default('pdf');
            $table->boolean('include_charts')->default(true);
            $table->boolean('include_raw_data')->default(false);

            // Configuración de contenido
            $table->string('template')->nullable(); // Template personalizado
            $table->json('chart_configs')->nullable(); // Configuración de gráficos
            $table->text('custom_message')->nullable(); // Mensaje personalizado en el reporte
            $table->json('branding')->nullable(); // Logo, colores, etc.

            // Configuración de retención
            $table->integer('retention_days')->default(90); // Cuánto tiempo guardar reportes
            $table->boolean('archive_reports')->default(true);

            // Condiciones y alertas
            $table->json('alert_conditions')->nullable(); // Condiciones que disparan alertas
            $table->json('alert_thresholds')->nullable(); // Umbrales para alertas
            $table->boolean('send_only_on_changes')->default(false); // Solo enviar si hay cambios significativos

            // Metadatos de ejecución
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('execution_count')->default(0);
            $table->text('last_error')->nullable();
            $table->enum('last_status', ['success', 'failed', 'partial'])->nullable();

            // Metadatos de creación
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['status', 'next_run_at']);
            $table->index(['report_type', 'frequency']);
            $table->index(['created_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automated_reports');
    }
};
