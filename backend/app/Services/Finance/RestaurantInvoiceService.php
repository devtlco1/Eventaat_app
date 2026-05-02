<?php

namespace App\Services\Finance;

use App\Enums\RestaurantInvoiceStatus;
use App\Models\RestaurantInvoice;
use Illuminate\Support\Facades\DB;
use Throwable;

class RestaurantInvoiceService
{
    /**
     * Sequential per-calendar-year invoice numbers: INV-{YYYY}-{000001}.
     * Uses a transaction + lock to reduce collisions under concurrency.
     */
    public function generateUniqueInvoiceNumber(): string
    {
        return DB::transaction(function (): string {
            $year = now()->year;
            $prefix = "INV-{$year}-";

            $numbers = RestaurantInvoice::query()
                ->where('invoice_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->pluck('invoice_number');

            $maxSeq = 0;
            foreach ($numbers as $num) {
                if (preg_match('/^INV-\d+-(\d{6})$/', (string) $num, $m)) {
                    $maxSeq = max($maxSeq, (int) $m[1]);
                }
            }

            return $prefix.str_pad((string) ($maxSeq + 1), 6, '0', STR_PAD_LEFT);
        });
    }

    public function markIssued(RestaurantInvoice $invoice): void
    {
        if ($invoice->status !== RestaurantInvoiceStatus::Draft) {
            return;
        }

        try {
            $invoice->forceFill([
                'status' => RestaurantInvoiceStatus::Issued,
                'issue_date' => $invoice->issue_date ?? now()->toDateString(),
            ])->save();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function markPaid(RestaurantInvoice $invoice): void
    {
        if (! in_array($invoice->status, [RestaurantInvoiceStatus::Issued, RestaurantInvoiceStatus::Overdue], true)) {
            return;
        }

        try {
            $invoice->forceFill([
                'status' => RestaurantInvoiceStatus::Paid,
                'paid_at' => $invoice->paid_at ?? now(),
            ])->save();
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function markVoid(RestaurantInvoice $invoice): void
    {
        if (! in_array($invoice->status, [
            RestaurantInvoiceStatus::Draft,
            RestaurantInvoiceStatus::Issued,
            RestaurantInvoiceStatus::Overdue,
        ], true)) {
            return;
        }

        try {
            $invoice->forceFill([
                'status' => RestaurantInvoiceStatus::Void,
            ])->save();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
