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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id');
            $table->string('document_type')->default('CC')->after('password');
            $table->string('document_number')->nullable()->after('document_type');
            $table->string('phone')->nullable()->after('document_number');
            $table->string('mobile')->nullable()->after('phone');
            $table->string('position')->nullable()->after('mobile');
            $table->text('address')->nullable()->after('position');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('set null')->after('address');
            $table->foreignId('state_id')->nullable()->constrained()->onDelete('set null')->after('city_id');
            $table->foreignId('country_id')->nullable()->constrained()->onDelete('set null')->after('state_id');
            $table->string('avatar')->nullable()->after('country_id');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes()->after('updated_at');
            
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['country_id']);
            $table->dropColumn([
                'company_id', 'document_type', 'document_number', 'phone', 'mobile',
                'position', 'address', 'city_id', 'state_id', 'country_id',
                'avatar', 'is_active', 'last_login_at', 'deleted_at'
            ]);
        });
    }
};
