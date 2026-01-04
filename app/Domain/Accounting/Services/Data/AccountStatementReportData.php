<?php

namespace App\Domain\Accounting\Services\Data;

use Carbon\Carbon;

readonly class AccountStatementReportData
{
    public function __construct(
        public int $journalId,
        public string $journalReference,
        public Carbon $journalDate,
        public ?string $journalDescription,
        public int $journalLineId,
        public float $debit,
        public float $credit,
        public ?string $lineDescription
    ) {
    }
}

