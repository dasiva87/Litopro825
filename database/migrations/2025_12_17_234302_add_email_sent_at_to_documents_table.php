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
        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('email_sent_at')->nullable()->after('valid_until');
            $table->unsignedBigInteger('email_sent_by')->nullable()->after('email_sent_at');
            $table->foreign('email_sent_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['email_sent_by']);
            $table->dropColumn(['email_sent_at', 'email_sent_by']);
        });
    }
};
