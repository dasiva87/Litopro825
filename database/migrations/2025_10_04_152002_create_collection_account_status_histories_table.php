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
        Schema::create('collection_account_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collection_account_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('collection_account_id', 'coll_acct_sh_coll_acct_fk')
                  ->references('id')->on('collection_accounts')->cascadeOnDelete();
            $table->foreign('user_id', 'coll_acct_sh_user_fk')
                  ->references('id')->on('users')->nullOnDelete();
            $table->index(['collection_account_id', 'created_at'], 'idx_coll_acct_sh_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_account_status_histories');
    }
};
