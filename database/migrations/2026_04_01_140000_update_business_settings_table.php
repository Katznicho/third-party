<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('business_settings')) {
            return;
        }
        if (! Schema::hasColumn('business_settings', 'business_id')) {
            return;
        }
        if (Schema::hasColumn('business_settings', 'business_connection_id')) {
            return;
        }
        Schema::table('business_settings', function (Blueprint $table) {
            $table->renameColumn('business_id', 'business_connection_id');
        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->renameColumn('business_connection_id', 'business_id');
        });
    }
};
