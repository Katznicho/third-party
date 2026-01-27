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
        Schema::table('policies', function (Blueprint $table) {
            // Co-payment: Fixed amount payable at each visit
            $table->decimal('copay_amount', 15, 2)->nullable()->after('has_deductible');
            
            // Coinsurance: Fixed percentage paid on all invoices of a particular visit
            $table->decimal('coinsurance_percentage', 5, 2)->nullable()->after('copay_amount');
            
            // Deductible: Amount client has to pay before insurance starts paying
            $table->decimal('deductible_amount', 15, 2)->nullable()->after('coinsurance_percentage');
            
            // Maximum limit (cap) for copayments
            $table->decimal('copay_max_limit', 15, 2)->nullable()->after('deductible_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn([
                'copay_amount',
                'coinsurance_percentage',
                'deductible_amount',
                'copay_max_limit',
            ]);
        });
    }
};
