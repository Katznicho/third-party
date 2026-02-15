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
        Schema::table('payments', function (Blueprint $table) {
            // Make policy_id nullable to allow payments without a policy (e.g., from Kashtre invoices)
            $table->foreignId('policy_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Note: This will fail if there are any NULL policy_id values
            // You may need to update those records first
            $table->foreignId('policy_id')->nullable(false)->change();
        });
    }
};
