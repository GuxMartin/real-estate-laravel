<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeamIdToStaffResourceTables extends Migration
{
    public function up()
    {
        $tables = [
            'appointments', 'buyers', 'contractors', 'document_templates',
            'favorites', 'images', 'key_locations', 'property_features',
            'properties', 'reviews', 'tenants', 'transactions'
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
                });
            }
        }
    }

    public function down()
    {
        $tables = [
            'appointments', 'buyers', 'contractors', 'document_templates',
            'favorites', 'images', 'key_locations', 'property_features',
            'properties', 'reviews', 'tenants', 'transactions'
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['team_id']);
                    $table->dropColumn('team_id');
                });
            }
        }
    }
}