<?php

namespace App\Domain\Accounting\Services\Data;

readonly class IncomeStatementReportData
{
    public function __construct(
        public int $accountId,
        public string $accountCode,
        public string $accountName,
        public float $amount
    ) {
    }
}

