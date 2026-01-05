<?php

namespace App\Domain\Accounting\Listeners;

use App\Domain\Accounting\Events\JournalPosted;
use App\Services\AuditLogger;

class LogJournalPosted
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {
    }

    public function handle(JournalPosted $event): void
    {
        $journal = $event->journal;

        // Calculate totals from journal lines
        $debitTotal = $journal->journalLines()->sum('debit');
        $creditTotal = $journal->journalLines()->sum('credit');

        $meta = [
            'journal_id' => $journal->id,
            'reference' => $journal->reference,
            'reference_type' => $journal->reference_type,
            'reference_id' => $journal->reference_id,
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
            'journal_date' => $journal->journal_date?->format('Y-m-d'),
            'posted_at' => $journal->posted_at?->toIso8601String(),
            'posted_by' => $journal->posted_by,
        ];

        $this->auditLogger->log(
            action: 'journal_posted',
            subject: $journal,
            meta: $meta,
            branchId: $journal->branch_id
        );
    }
}

