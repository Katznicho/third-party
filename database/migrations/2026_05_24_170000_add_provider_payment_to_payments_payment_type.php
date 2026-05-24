<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Provider (connected company) payments use payment_type = provider_payment.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_type ENUM(
            'premium_payment',
            'claim_settlement',
            'refund',
            'adjustment',
            'partial_payment',
            'full_payment',
            'provider_payment'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('payments')->where('payment_type', 'provider_payment')->delete();

        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_type ENUM(
            'premium_payment',
            'claim_settlement',
            'refund',
            'adjustment',
            'partial_payment',
            'full_payment'
        ) NOT NULL");
    }
};
