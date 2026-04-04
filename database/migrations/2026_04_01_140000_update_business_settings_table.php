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
        Schema::table('business_settings', function (Blueprint $table) {
            // Rename column to business_connection_id
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
