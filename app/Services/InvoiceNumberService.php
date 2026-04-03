<?php

namespace App\Services;

use App\Models\Sale;
use Carbon\Carbon;

class InvoiceNumberService
{
    public function generate(): string
    {
        $year = Carbon::now()->year;
        $prefix = "INV-{$year}-";

        $last = Sale::query()
            ->where('invoice_no', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('invoice_no');

        $next = 1;

        if ($last) {
            $parts = explode('-', $last);
            $next = ((int) end($parts)) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
