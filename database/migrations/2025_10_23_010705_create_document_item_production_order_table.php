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
        Schema::create('document_item_production_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();

            // Production-specific data (calculated from SimpleItem)
            $table->integer('quantity_to_produce'); // Cantidad a producir
            $table->integer('sheets_needed'); // Pliegos necesarios
            $table->decimal('total_impressions', 12, 2); // Millares (sheets × inks)

            // Ink details (copied from SimpleItem at production time)
            $table->integer('ink_front_count')->default(0);
            $table->integer('ink_back_count')->default(0);
            $table->boolean('front_back_plate')->default(false);

            // Paper details
            $table->foreignId('paper_id')->nullable()->constrained('papers')->nullOnDelete();
            $table->decimal('horizontal_size', 8, 2)->nullable();
            $table->decimal('vertical_size', 8, 2)->nullable();

            // Production status
            $table->integer('produced_quantity')->default(0);
            $table->integer('rejected_quantity')->default(0);
            $table->string('item_status')->default('pending'); // pending, in_progress, completed, paused

            // Production tracking
            $table->dateTime('production_started_at')->nullable();
            $table->dateTime('production_completed_at')->nullable();
            $table->decimal('actual_impressions', 12, 2)->nullable(); // Real impressions used

            // Notes
            $table->text('production_notes')->nullable();
            $table->text('quality_notes')->nullable();

            $table->timestamps();

            // Indexes (with short names to avoid MySQL 64 char limit)
            $table->index(['production_order_id', 'item_status'], 'dipr_prod_order_status_idx');
            $table->index('document_item_id', 'dipr_doc_item_idx');
            $table->unique(['document_item_id', 'production_order_id'], 'dipr_doc_prod_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_item_production_order');
    }
};
