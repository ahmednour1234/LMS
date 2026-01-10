<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\Journal;
use App\Domain\Accounting\Models\JournalLine;
use App\Domain\Accounting\Services\Data\AccountStatementReportData;
use App\Domain\Accounting\Services\Data\DeferredRevenueReportData;
use App\Domain\Accounting\Services\Data\GeneralLedgerReportData;
use App\Domain\Accounting\Services\Data\IncomeStatementReportData;
use App\Domain\Accounting\Services\Data\TrialBalanceReportData;
use App\Enums\JournalStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getTrialBalance(
        Carbon $reportDate,
        User $user
    ): Collection {
        $query = Journal::query()
            ->join('journal_lines', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journals.status', JournalStatus::POSTED)
            ->where('journals.journal_date', '<=', $reportDate)
            ->where('accounts.is_active', true)
            ->select([
                'accounts.id as account_id',
                'accounts.code as account_code',
                'accounts.name as account_name',
                'accounts.type as account_type',
                'accounts.opening_balance',
                DB::raw('SUM(journal_lines.debit) as total_debit'),
                DB::raw('SUM(journal_lines.credit) as total_credit'),
            ])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.opening_balance');

        $query = $query->visibleTo($user, 'journals.view');

        $results = $query->get();

        return $results->map(function ($row) {
            $openingBalance = (float) $row->opening_balance;
            $totalDebit = (float) $row->total_debit;
            $totalCredit = (float) $row->total_credit;
            $closingBalance = $openingBalance + ($totalDebit - $totalCredit);

            return new TrialBalanceReportData(
                accountId: $row->account_id,
                accountCode: $row->account_code,
                accountName: $row->account_name,
                accountType: $row->account_type,
                openingBalance: $openingBalance,
                totalDebit: $totalDebit,
                totalCredit: $totalCredit,
                closingBalance: $closingBalance
            );
        })->sortBy('account_code');
    }

    public function getGeneralLedger(
        Carbon $startDate,
        Carbon $endDate,
        ?array $accountIds = null,
        User $user
    ): Collection {
        $query = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->leftJoin('payments', function ($join) {
                $join->where('journals.reference_type', '=', 'payment')
                     ->whereColumn('journals.reference_id', 'payments.id');
            })
            ->where('journals.status', JournalStatus::POSTED)
            ->whereBetween('journals.journal_date', [$startDate, $endDate])
            ->where('accounts.is_active', true)
            ->select([
                'journals.id as journal_id',
                'journals.reference',
                'journals.reference_type',
                'journals.reference_id',
                'journals.journal_date as date',
                'journals.description as journal_description',
                'journal_lines.id as journal_line_id',
                'journal_lines.account_id',
                'accounts.code as account_code',
                'accounts.name as account_name',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_lines.memo as line_description',
                'journal_lines.cost_center_id',
                'payments.id as payment_id',
                'payments.method as payment_method',
                DB::raw("CASE WHEN journals.reference_type = 'payment' THEN CONCAT('PAY-', payments.id) ELSE NULL END as payment_reference"),
            ])
            ->orderBy('journals.journal_date')
            ->orderBy('journals.id')
            ->orderBy('journal_lines.id');

        if ($accountIds !== null && count($accountIds) > 0) {
            $query->whereIn('journal_lines.account_id', $accountIds);
        }

        $journalQuery = Journal::query()->visibleTo($user, 'journals.view');
        $visibleJournalIds = $journalQuery->pluck('id')->toArray();

        if (empty($visibleJournalIds)) {
            return collect();
        }

        $query->whereIn('journals.id', $visibleJournalIds);

        $results = $query->get();

        $runningBalances = [];
        $openingBalances = [];

        $results->each(function ($row) use (&$openingBalances, $startDate, $visibleJournalIds) {
            $accountId = $row->account_id;
            if (!isset($openingBalances[$accountId])) {
                $account = Account::find($accountId);
                $openingBalances[$accountId] = (float) ($account->opening_balance ?? 0);

                $openingQuery = JournalLine::query()
                    ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
                    ->where('journal_lines.account_id', $accountId)
                    ->where('journals.status', JournalStatus::POSTED)
                    ->where('journals.journal_date', '<', $startDate)
                    ->whereIn('journals.id', $visibleJournalIds)
                    ->select(
                        DB::raw('COALESCE(SUM(journal_lines.debit), 0) - COALESCE(SUM(journal_lines.credit), 0) as balance')
                    );

                $openingBalance = (float) ($openingQuery->value('balance') ?? 0);
                $openingBalances[$accountId] += $openingBalance;
            }
        });

        return $results->map(function ($row) use (&$runningBalances, &$openingBalances) {
            $accountId = $row->account_id;
            if (!isset($runningBalances[$accountId])) {
                $runningBalances[$accountId] = $openingBalances[$accountId] ?? 0;
            }

            $runningBalances[$accountId] += (float) $row->debit - (float) $row->credit;

            return new GeneralLedgerReportData(
                journalId: $row->journal_id,
                journalReference: $row->reference,
                journalDate: Carbon::parse($row->date),
                journalDescription: $row->journal_description,
                journalLineId: $row->journal_line_id,
                accountId: $row->account_id,
                accountCode: $row->account_code,
                accountName: $row->account_name,
                debit: (float) $row->debit,
                credit: (float) $row->credit,
                lineDescription: $row->line_description,
                costCenterId: $row->cost_center_id,
                runningBalance: $runningBalances[$accountId],
                paymentId: $row->payment_id ? (int) $row->payment_id : null,
                paymentMethod: $row->payment_method ?? null,
                paymentReference: $row->payment_reference ?? null
            );
        });
    }

    public function getDeferredRevenueReport(
        Carbon $startDate,
        Carbon $endDate,
        ?int $branchId = null,
        ?string $referenceType = null,
        User $user
    ): Collection {
        $deferredRevenueAccount = Account::where('code', '2130')
            ->where('is_active', true)
            ->first();

        if (!$deferredRevenueAccount) {
            return collect();
        }

        $openingBalanceQuery = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journal_lines.account_id', $deferredRevenueAccount->id)
            ->where('journals.status', JournalStatus::POSTED)
            ->where('journals.date', '<', $startDate)
            ->select(
                DB::raw('COALESCE(SUM(journal_lines.credit), 0) - COALESCE(SUM(journal_lines.debit), 0) as opening_balance')
            );

        $journalQuery = Journal::query()->visibleTo($user, 'journals.view');
        $visibleJournalIds = $journalQuery->pluck('id')->toArray();

        if (empty($visibleJournalIds)) {
            return collect();
        }

        $openingBalanceQuery->whereIn('journals.id', $visibleJournalIds);
        $openingBalance = (float) ($openingBalanceQuery->value('opening_balance') ?? 0);

        $periodQuery = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journal_lines.account_id', $deferredRevenueAccount->id)
            ->where('journals.status', JournalStatus::POSTED)
            ->whereBetween('journals.date', [$startDate, $endDate])
            ->whereIn('journals.id', $visibleJournalIds)
            ->select([
                'journals.id',
                'journals.reference',
                'journals.journal_date as date',
                'journals.description',
                'journals.branch_id',
                'journals.reference_type',
                'journals.reference_id',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_lines.memo as line_description',
            ])
            ->orderBy('journals.journal_date')
            ->orderBy('journals.id');

        if ($branchId !== null) {
            $periodQuery->where('journals.branch_id', $branchId);
        }

        if ($referenceType !== null) {
            $periodQuery->where('journals.reference_type', $referenceType);
        }

        $periodTransactions = $periodQuery->get();

        $totalDeferred = $periodTransactions->sum('credit');
        $totalRecognized = $periodTransactions->sum('debit');
        $closingBalance = $openingBalance + $totalDeferred - $totalRecognized;

        $transactions = $periodTransactions->map(function ($row) {
            return new DeferredRevenueReportData(
                journalId: $row->id,
                journalReference: $row->reference,
                journalDate: $row->date,
                journalDescription: $row->description,
                branchId: $row->branch_id,
                referenceType: $row->reference_type,
                referenceId: $row->reference_id,
                debit: (float) $row->debit,
                credit: (float) $row->credit,
                lineDescription: $row->line_description,
                openingBalance: null,
                closingBalance: null
            );
        });

        $summary = new DeferredRevenueReportData(
            journalId: null,
            journalReference: 'SUMMARY',
            journalDate: null,
            journalDescription: 'Period Summary',
            branchId: $branchId,
            referenceType: $referenceType,
            referenceId: null,
            debit: $totalRecognized,
            credit: $totalDeferred,
            lineDescription: null,
            openingBalance: $openingBalance,
            closingBalance: $closingBalance
        );

        return collect([$summary])->merge($transactions);
    }

    public function getIncomeStatement(
        Carbon $startDate,
        Carbon $endDate,
        ?int $branchId = null,
        User $user
    ): array {
        $journalQuery = Journal::query()->visibleTo($user, 'journals.view');
        $visibleJournalIds = $journalQuery->pluck('id')->toArray();

        if (empty($visibleJournalIds)) {
            return [
                'revenues' => collect(),
                'expenses' => collect(),
            ];
        }

        // Get revenue accounts (type = 'revenue')
        $revenueQuery = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journals.status', JournalStatus::POSTED)
            ->whereBetween('journals.journal_date', [$startDate, $endDate])
            ->where('accounts.type', 'revenue')
            ->where('accounts.is_active', true)
            ->whereIn('journals.id', $visibleJournalIds)
            ->select([
                'accounts.id as account_id',
                'accounts.code as account_code',
                'accounts.name as account_name',
                DB::raw('SUM(journal_lines.credit - journal_lines.debit) as amount'),
            ])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name');

        // Get expense accounts (type = 'expense')
        $expenseQuery = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('journals.status', JournalStatus::POSTED)
            ->whereBetween('journals.journal_date', [$startDate, $endDate])
            ->where('accounts.type', 'expense')
            ->where('accounts.is_active', true)
            ->whereIn('journals.id', $visibleJournalIds)
            ->select([
                'accounts.id as account_id',
                'accounts.code as account_code',
                'accounts.name as account_name',
                DB::raw('SUM(journal_lines.debit - journal_lines.credit) as amount'),
            ])
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name');

        if ($branchId !== null) {
            $revenueQuery->where('journals.branch_id', $branchId);
            $expenseQuery->where('journals.branch_id', $branchId);
        }

        $revenues = $revenueQuery->get()->map(function ($row) {
            return new IncomeStatementReportData(
                accountId: $row->account_id,
                accountCode: $row->account_code,
                accountName: $row->account_name,
                amount: (float) $row->amount
            );
        })->filter(fn ($item) => $item->amount > 0.01)->sortBy('accountCode');

        $expenses = $expenseQuery->get()->map(function ($row) {
            return new IncomeStatementReportData(
                accountId: $row->account_id,
                accountCode: $row->account_code,
                accountName: $row->account_name,
                amount: (float) $row->amount
            );
        })->filter(fn ($item) => $item->amount > 0.01)->sortBy('accountCode');

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
        ];
    }

    public function getAccountStatement(
        int $accountId,
        Carbon $startDate,
        Carbon $endDate,
        User $user
    ): array {
        $account = Account::find($accountId);

        if (!$account) {
            return [
                'account' => null,
                'openingBalance' => 0,
                'data' => collect(),
            ];
        }

        $journalQuery = Journal::query()->visibleTo($user, 'journals.view');
        $visibleJournalIds = $journalQuery->pluck('id')->toArray();

        if (empty($visibleJournalIds)) {
            return [
                'account' => $account,
                'openingBalance' => 0,
                'data' => collect(),
            ];
        }

        // Calculate opening balance
        $openingQuery = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journal_lines.account_id', $accountId)
            ->where('journals.status', JournalStatus::POSTED)
            ->where('journals.journal_date', '<', $startDate)
            ->whereIn('journals.id', $visibleJournalIds)
            ->select(
                DB::raw('COALESCE(SUM(journal_lines.debit), 0) - COALESCE(SUM(journal_lines.credit), 0) as balance')
            );

        $openingBalance = (float) ($account->opening_balance ?? 0);
        $openingBalance += (float) ($openingQuery->value('balance') ?? 0);

        // Get period transactions
        $query = JournalLine::query()
            ->join('journals', 'journal_lines.journal_id', '=', 'journals.id')
            ->where('journal_lines.account_id', $accountId)
            ->where('journals.status', JournalStatus::POSTED)
            ->whereBetween('journals.journal_date', [$startDate, $endDate])
            ->whereIn('journals.id', $visibleJournalIds)
            ->select([
                'journals.id as journal_id',
                'journals.reference as journal_reference',
                'journals.journal_date as date',
                'journals.description as journal_description',
                'journal_lines.id as journal_line_id',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_lines.memo as line_description',
            ])
            ->orderBy('journals.journal_date')
            ->orderBy('journals.id')
            ->orderBy('journal_lines.id');

        $results = $query->get();

        $runningBalance = $openingBalance;
        $data = $results->map(function ($row) use (&$runningBalance) {
            $runningBalance += (float) $row->debit - (float) $row->credit;
            
            return new AccountStatementReportData(
                journalId: $row->journal_id,
                journalReference: $row->journal_reference,
                journalDate: \Carbon\Carbon::parse($row->date),
                journalDescription: $row->journal_description,
                journalLineId: $row->journal_line_id,
                debit: (float) $row->debit,
                credit: (float) $row->credit,
                lineDescription: $row->line_description,
                runningBalance: $runningBalance
            );
        });

        return [
            'account' => $account,
            'openingBalance' => $openingBalance,
            'data' => $data,
        ];
    }
}

