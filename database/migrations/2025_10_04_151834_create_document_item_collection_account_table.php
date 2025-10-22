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
        Schema::create('document_item_collection_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('collection_account_id')->constrained()->onDelete('cascade');

            $table->decimal('quantity_ordered', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending', 'billed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['document_item_id', 'collection_account_id'], 'unique_item_per_coll_account');
            $table->index('document_item_id', 'idx_doc_item_coll_account');
            $table->index(['collection_account_id', 'status'], 'idx_coll_account_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_item_collection_account');
    }
};
