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
        Schema::create('paper_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('paper_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('paper_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('marketplace_offer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description');
            $table->integer('quantity');
            $table->string('unit_measure');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->json('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['paper_order_id']);
            $table->index(['paper_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paper_order_items');
    }
};
