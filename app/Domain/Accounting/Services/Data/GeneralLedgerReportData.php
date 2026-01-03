<?php

namespace App\Domain\Accounting\Services\Data;

use Carbon\Carbon;

readonly class GeneralLedgerReportData
{
    public function __construct(
        public int $journalId,
        public string $journalReference,
        public Carbon $journalDate,
        public ?string $journalDescription,
        public int $journalLineId,
        public int $accountId,
        public string $accountCode,
        public string $accountName,
        public float $debit,
        public float $credit,
        public ?string $lineDescription,
        public ?int $costCenterId,
        public float $runningBalance
    ) {
    }
}

