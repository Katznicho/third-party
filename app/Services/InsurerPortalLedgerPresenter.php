<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Expands insurer-portal ledger JSON (from Kashtre API) into item-level rows for debits.
 * Mirrors Kashtre's ThirdPartyPayerStatementPresenter logic using plain arrays.
 */
class InsurerPortalLedgerPresenter
{
    /**
     * @param  Collection<int, array<string, mixed>>  $histories
     * @return Collection<int, array<string, mixed>>
     */
    public static function rowsFromHistoryArrays(Collection $histories): Collection
    {
        return $histories->flatMap(fn (array $h) => self::rowsForHistoryArray($h));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function rowsForHistoryArray(array $h): Collection
    {
        $type = $h['transaction_type'] ?? '';
        $invoice = $h['invoice'] ?? null;
        $items = is_array($invoice) ? ($invoice['items'] ?? []) : [];
        $lines = self::normalizedInvoiceLines($items);

        if ($type === 'debit' && $lines->isNotEmpty()) {
            $totalDebit = abs((float) ($h['change_amount'] ?? 0));
            $subtotal = $lines->sum(fn (array $line) => self::lineAmount($line));

            if ($totalDebit > 0 && $subtotal > 0) {
                return self::expandedDebitRows($h, $lines, $totalDebit, $subtotal);
            }
        }

        return collect([self::singleLedgerRowArray($h)]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     * @return Collection<int, array<string, mixed>>
     */
    private static function expandedDebitRows(
        array $h,
        Collection $lines,
        float $totalDebit,
        float $subtotal
    ): Collection {
        $count = $lines->count();
        $rows = collect();
        $remaining = $totalDebit;

        foreach ($lines->values() as $idx => $line) {
            $share = self::lineAmount($line) / $subtotal;
            $isLast = ($idx === $count - 1);
            $portion = $isLast ? max(0, round($remaining, 2)) : round($share * $totalDebit, 2);
            $remaining -= $portion;

            $name = trim((string) ($line['name'] ?? $line['displayName'] ?? ''));
            if ($name === '') {
                $name = 'Line item';
            }
            $qty = (float) ($line['quantity'] ?? 1);
            $qtyDisp = ($qty != floor($qty)) ? $qty : (int) $qty;
            $label = $qtyDisp > 1 ? "{$name} (×{$qtyDisp})" : $name;

            $parentDesc = trim((string) ($h['description'] ?? ''));
            $detail = ($count > 1 && $parentDesc !== '' && stripos($parentDesc, $name) === false)
                ? $parentDesc
                : ($h['description'] ?? null);

            $runningBalance = $isLast && isset($h['new_balance'])
                ? (float) $h['new_balance']
                : null;

            $rows->push(self::buildRow($h, $label, max(0.0, $portion), $detail, $runningBalance));
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private static function singleLedgerRowArray(array $h): array
    {
        $invoice = $h['invoice'] ?? null;
        $desc = (string) ($h['description'] ?? '');
        $matched = self::matchInvoiceItemLabel($desc, $invoice);
        $lineLabel = $matched ?? self::fallbackLineLabel($invoice, $desc);

        return self::buildRow(
            $h,
            $lineLabel !== '' ? $lineLabel : '—',
            abs((float) ($h['change_amount'] ?? 0)),
            $h['description'] ?? null,
            isset($h['new_balance']) ? (float) $h['new_balance'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildRow(
        array $h,
        string $lineLabel,
        float $amount,
        ?string $detailDescription,
        ?float $newBalance
    ): array {
        $invoice = $h['invoice'] ?? null;

        return [
            'history_id' => $h['id'] ?? null,
            'created_at' => $h['created_at'] ?? null,
            'line_label' => $lineLabel,
            'detail_description' => $detailDescription,
            'client' => $h['client'] ?? null,
            'invoice' => is_array($invoice) ? [
                'id' => $invoice['id'] ?? null,
                'invoice_number' => $invoice['invoice_number'] ?? null,
            ] : null,
            'transaction_type' => $h['transaction_type'] ?? '',
            'amount' => $amount,
            'payment_method' => $h['payment_method'] ?? null,
            'payment_status' => $h['payment_status'] ?? null,
            'new_balance' => $newBalance,
        ];
    }

    /**
     * @param  array<int, mixed>|null  $items
     */
    private static function normalizedInvoiceLines(?array $items): Collection
    {
        if ($items === null || $items === []) {
            return collect();
        }

        return collect($items)
            ->filter(fn ($line) => is_array($line))
            ->values();
    }

    /**
     * @param  array<string, mixed>|null  $invoice
     */
    private static function matchInvoiceItemLabel(?string $description, ?array $invoice): ?string
    {
        if ($invoice === null || $description === null || trim($description) === '') {
            return null;
        }

        $items = $invoice['items'] ?? [];
        if (! is_array($items) || $items === []) {
            return null;
        }

        foreach ($items as $line) {
            if (! is_array($line)) {
                continue;
            }
            $name = trim((string) ($line['name'] ?? $line['displayName'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (stripos($description, $name) !== false) {
                $qty = (float) ($line['quantity'] ?? 1);
                $qtyDisp = ($qty != floor($qty)) ? $qty : (int) $qty;

                return $qtyDisp > 1 ? "{$name} (×{$qtyDisp})" : $name;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $invoice
     */
    private static function fallbackLineLabel(?array $invoice, string $description): string
    {
        if ($description !== '') {
            return $description;
        }

        $items = $invoice['items'] ?? [];
        if (! is_array($items) || $items === []) {
            return '';
        }

        if (count($items) === 1 && is_array($items[0])) {
            $name = trim((string) ($items[0]['name'] ?? $items[0]['displayName'] ?? ''));

            return $name !== '' ? $name : '';
        }

        $names = collect($items)
            ->filter(fn ($line) => is_array($line))
            ->map(fn ($line) => trim((string) ($line['name'] ?? $line['displayName'] ?? '')))
            ->filter()
            ->take(3)
            ->implode(', ');

        return $names !== '' ? $names.' (itemized)' : '';
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private static function lineAmount(array $line): float
    {
        if (isset($line['total_amount']) && is_numeric($line['total_amount'])) {
            return max(0.0, (float) $line['total_amount']);
        }

        $price = isset($line['price']) && is_numeric($line['price']) ? (float) $line['price'] : 0.0;
        $qty = isset($line['quantity']) && is_numeric($line['quantity']) ? (float) $line['quantity'] : 1.0;

        return max(0.0, $price * $qty);
    }
}
