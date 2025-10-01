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
        Schema::table('document_items', function (Blueprint $table) {
            $table->enum('order_status', ['available', 'in_cart', 'ordered', 'received'])
                  ->default('available')
                  ->after('total_price');

            $table->index(['order_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            $table->dropIndex(['order_status']);
            $table->dropColumn('order_status');
        });
    }
};
