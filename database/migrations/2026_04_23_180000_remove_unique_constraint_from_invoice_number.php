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
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the unique constraint on invoice_number
            // Allows multiple vendors to have transactions for same Kashtre invoice
            $table->dropUnique(['invoice_number']);
            // Keep it as a regular index for queries
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_number']);
            $table->unique('invoice_number');
        });
    }
};
