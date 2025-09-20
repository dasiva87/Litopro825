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
        Schema::create('usage_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('metric_type'); // users, documents, storage, api_calls, etc.
            $table->decimal('value', 15, 2);
            $table->string('unit')->nullable(); // GB, MB, count, etc.
            $table->date('period_start');
            $table->date('period_end');
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            $table->index(['company_id', 'metric_type', 'period_start']);
            $table->index(['metric_type', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_metrics');
    }
};
