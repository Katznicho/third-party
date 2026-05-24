<?php

namespace App\Support;

/**
 * Human-readable Yo Payments errors for UI and logs (no credentials in output).
 */
class YoPaymentsErrorFormatter
{
    public static function credentialsError(): ?string
    {
        $username = (string) config('payments.yo_username', '');
        $password = (string) config('payments.yo_password', '');

        if ($username === '' || $password === '') {
            return 'Yo Payments is not configured on this server. Set YO_PAYMENTS_USERNAME and YO_PAYMENTS_PASSWORD in the insurer portal .env file.';
        }

        return null;
    }

    public static function webhookUrlWarning(): ?string
    {
        $url = (string) config('payments.webhook_url', '');

        if ($url === '' || str_contains($url, 'webhook.site')) {
            return 'YO_PAYMENTS_WEBHOOK_URL is not set to a production callback URL (optional; status checks still work via “Check payment status” and the payments:check-status job).';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $yoResult
     */
    public static function formatDepositInitiationFailure(array $yoResult): string
    {
        $detail = self::formatResponseDetails($yoResult);

        if (($yoResult['Status'] ?? '') === 'OK' && empty($yoResult['TransactionReference'])) {
            return 'Mobile money request was accepted by Yo but no transaction reference was returned. '.$detail;
        }

        return 'Mobile money request failed. '.$detail;
    }

    /**
     * @param  array<string, mixed>  $statusCheck
     */
    public static function formatStatusCheckFailure(array $statusCheck): string
    {
        if ($statusCheck === []) {
            return 'Yo Payments returned an empty status response. Check server logs for the raw XML.';
        }

        return 'Could not read payment status from Yo. '.self::formatResponseDetails($statusCheck);
    }

    /**
     * @param  array<string, mixed>  $statusCheck
     */
    public static function formatPendingStatus(array $statusCheck): string
    {
        $detail = trim((string) ($statusCheck['StatusMessage'] ?? ''));

        return $detail !== ''
            ? 'Payment is still pending on mobile money: '.$detail
            : 'Payment is still pending on mobile money. Complete the prompt on the phone and check again.';
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function formatResponseDetails(array $response): string
    {
        $parts = [];

        foreach (['Status', 'StatusCode', 'TransactionStatus', 'ErrorMessageCode'] as $key) {
            if (! empty($response[$key])) {
                $parts[] = $key.': '.$response[$key];
            }
        }

        $message = trim((string) ($response['ErrorMessage'] ?? $response['StatusMessage'] ?? ''));
        if ($message !== '' && ! in_array('StatusMessage: '.$message, $parts, true) && ! in_array('ErrorMessage: '.$message, $parts, true)) {
            $parts[] = $message;
        }

        if (! empty($response['TransactionReference'])) {
            $parts[] = 'TransactionReference: '.$response['TransactionReference'];
        }

        return $parts !== [] ? implode(' | ', $parts) : 'No detail returned by Yo Payments.';
    }
}
