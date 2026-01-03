<?php

namespace App\Domain\Accounting\Services\Data;

use Carbon\Carbon;

readonly class DeferredRevenueReportData
{
    public function __construct(
        public ?int $journalId,
        public string $journalReference,
        public ?Carbon $journalDate,
        public ?string $journalDescription,
        public ?int $branchId,
        public ?string $referenceType,
        public ?int $referenceId,
        public float $debit,
        public float $credit,
        public ?string $lineDescription,
        public ?float $openingBalance,
        public ?float $closingBalance
    ) {
    }
}

