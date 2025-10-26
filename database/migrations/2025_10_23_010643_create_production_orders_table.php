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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('production_number')->unique();

            // Supplier and operator assignment
            $table->foreignId('supplier_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Production workflow
            $table->string('status')->default('draft'); // draft, queued, in_progress, completed, cancelled, on_hold
            $table->date('scheduled_date')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // Production metrics (calculated from items)
            $table->decimal('total_impressions', 12, 2)->default(0); // Total millares
            $table->integer('total_items')->default(0);
            $table->decimal('estimated_hours', 8, 2)->nullable();

            // Notes and observations
            $table->text('notes')->nullable();
            $table->text('operator_notes')->nullable();

            // Quality control
            $table->boolean('quality_checked')->default(false);
            $table->foreignId('quality_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('quality_checked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'scheduled_date']);
            $table->index('supplier_id');
            $table->index('operator_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
