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
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Polymorphic relation to stockable items
            $table->morphs('stockable');

            // Alert details
            $table->enum('type', [
                'low_stock',           // Stock bajo
                'out_of_stock',        // Sin stock
                'critical_low',        // Crítico (menos del 20% del mínimo)
                'reorder_point',       // Punto de reorden
                'excess_stock',        // Exceso de stock
                'movement_anomaly'     // Movimiento anómalo
            ]);

            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['active', 'acknowledged', 'resolved', 'dismissed'])->default('active');

            // Stock information at time of alert
            $table->integer('current_stock');
            $table->integer('min_stock')->nullable();
            $table->integer('threshold_value')->nullable(); // Valor del umbral que disparó la alerta

            // Alert metadata
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable(); // Datos adicionales específicos del tipo de alerta

            // Tracking
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            // Auto-resolution
            $table->boolean('auto_resolvable')->default(true);
            $table->timestamp('expires_at')->nullable(); // Para alertas que expiran automáticamente

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'status', 'severity']);
            $table->index(['company_id', 'stockable_type', 'stockable_id']);
            $table->index(['triggered_at', 'status']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
