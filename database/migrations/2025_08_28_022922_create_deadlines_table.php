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
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('deadlinable_type');
            $table->unsignedBigInteger('deadlinable_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('deadline_date');
            $table->enum('deadline_type', [
                'document_delivery', 'quotation_expiry', 'production_deadline',
                'payment_due', 'material_order', 'equipment_maintenance',
                'client_followup', 'contract_renewal'
            ]);
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'completed', 'overdue', 'cancelled'])->default('pending');
            $table->boolean('reminder_sent')->default(false);
            $table->datetime('reminder_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['deadlinable_type', 'deadlinable_id']);
            $table->index(['company_id', 'status', 'deadline_date']);
            $table->index(['deadline_type', 'priority']);
            $table->index(['reminder_sent', 'reminder_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};
