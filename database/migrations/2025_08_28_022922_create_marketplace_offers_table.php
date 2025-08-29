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
        Schema::create('marketplace_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_contact_id')->constrained('contacts')->onDelete('cascade');
            $table->enum('product_type', ['paper', 'ink', 'finishing', 'equipment', 'consumables', 'services']);
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->json('specifications')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->string('currency', 3)->default('COP');
            $table->integer('minimum_quantity')->default(1);
            $table->integer('available_stock')->default(0);
            $table->string('unit_measure');
            $table->integer('delivery_time_days')->default(1);
            $table->json('delivery_locations')->nullable();
            $table->json('payment_terms')->nullable();
            $table->json('discount_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'product_type', 'is_active']);
            $table->index(['is_active', 'is_featured', 'expires_at']);
            $table->index(['product_type', 'unit_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_offers');
    }
};
