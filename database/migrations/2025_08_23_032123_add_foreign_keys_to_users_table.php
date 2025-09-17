<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Verificar qué constraints ya existen (creados por migración anterior con foreignId()->constrained())
            $existingConstraints = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'users'
                AND CONSTRAINT_NAME IN (
                    'users_company_id_foreign',
                    'users_city_id_foreign',
                    'users_state_id_foreign',
                    'users_country_id_foreign'
                )
            ");

            $existingNames = array_column($existingConstraints, 'CONSTRAINT_NAME');

            // Solo crear constraints que no existan
            if (!in_array('users_company_id_foreign', $existingNames)) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }

            if (!in_array('users_city_id_foreign', $existingNames)) {
                $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            }

            if (!in_array('users_state_id_foreign', $existingNames)) {
                $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
            }

            if (!in_array('users_country_id_foreign', $existingNames)) {
                $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['country_id']);
        });
    }
};