<?php

namespace App\Domain\Accounting\Services\Data;

readonly class TrialBalanceReportData
{
    public function __construct(
        public int $accountId,
        public string $accountCode,
        public string $accountName,
        public string $accountType,
        public float $openingBalance,
        public float $totalDebit,
        public float $totalCredit,
        public float $closingBalance
    ) {
    }
}

