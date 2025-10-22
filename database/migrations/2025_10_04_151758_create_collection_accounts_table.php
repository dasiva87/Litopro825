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
        Schema::create('collection_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_company_id')->constrained('companies')->onDelete('cascade');

            $table->string('account_number');
            $table->enum('status', ['draft', 'sent', 'approved', 'paid', 'cancelled'])
                  ->default('draft');

            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();

            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->unique(['account_number', 'company_id'], 'collection_accounts_number_company_unique');
            $table->index(['company_id', 'status']);
            $table->index(['client_company_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_accounts');
    }
};
