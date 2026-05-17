<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\PolicyPremiumPayment;

/**
 * Short, unique payment_reference values for the insurer app.
 * Avoids time()/uniqid()/base36 blobs that made /payments hard to read.
 */
class PaymentReference
{
    /**
     * Prefix + 10 hex chars (5 random bytes). Uniqueness enforced against payments + policy_premium_payments.
     */
    public static function unique(string $prefix): string
    {
        do {
            $ref = $prefix.strtoupper(bin2hex(random_bytes(5)));
        } while (self::isTaken($ref));

        return $ref;
    }

    private static function isTaken(string $ref): bool
    {
        return Payment::where('payment_reference', $ref)->exists()
            || PolicyPremiumPayment::where('payment_reference', $ref)->exists();
    }

    public static function forPremium(int $policyId): string
    {
        return self::unique('PREM-'.$policyId.'-');
    }

    /** Bulk invoice mobile money (Yo external ref + stored ref until Yo responds). */
    public static function forBulkMobileMoney(): string
    {
        return self::unique('MM-');
    }

    /** Single-invoice mobile: pending row before Yo returns TransactionReference. */
    public static function forMobilePending(): string
    {
        return self::unique('MM-');
    }

    public static function forBankOrCashDefault(): string
    {
        return self::unique('PAY-');
    }

    /** Yo ExternalReference for single-invoice MM request (invoice id is stored on the payment row). */
    public static function forInvoiceYoExternal(): string
    {
        return self::unique('INV-');
    }

    /** Manual “record client portion” from Payments UI. */
    public static function forClientPortionManual(): string
    {
        return self::unique('CP-');
    }

    /** Insurer payment to connected provider (Yo external ref + pending row). */
    public static function forProviderPayment(int $connectionId): string
    {
        return self::unique('PROV-'.$connectionId.'-');
    }
}
