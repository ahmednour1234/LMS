<?php

namespace App\Filament\Admin\Widgets;

use App\Domain\Accounting\Models\ArInstallment;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\RevenueRecognition;
use App\Domain\Enrollment\Models\Enrollment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        return [
            $this->getEnrollmentsTodayStat($branchId),
            $this->getPaymentsCollectedTodayStat($branchId),
            $this->getCollectionsThisMonthStat($branchId),
            $this->getOverdueInstallmentsStat($branchId),
            $this->getArOpenAmountStat($branchId),
            $this->getRecognizedRevenueThisMonthStat($branchId),
        ];
    }

    protected function getEnrollmentsTodayStat(?int $branchId): Stat
    {
        $count = Enrollment::whereDate('created_at', today())
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();

        return Stat::make('Enrollments Today', $count)
            ->description('New enrollments today')
            ->descriptionIcon('heroicon-m-user-plus')
            ->color('success');
    }

    protected function getPaymentsCollectedTodayStat(?int $branchId): Stat
    {
        $amount = Payment::where('status', 'completed')
            ->whereDate('paid_at', today())
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->sum('amount');

        return Stat::make('Payments Collected Today', number_format($amount, 2))
            ->description('Total payments received today')
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color('success');
    }

    protected function getCollectionsThisMonthStat(?int $branchId): Stat
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $amount = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->sum('amount');

        return Stat::make('Collections This Month', number_format($amount, 2))
            ->description('Total collections this month')
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('info');
    }

    protected function getOverdueInstallmentsStat(?int $branchId): Stat
    {
        $count = ArInstallment::where('due_date', '<', today())
            ->where('status', '!=', 'paid')
            ->when($branchId, function ($query) use ($branchId) {
                return $query->whereHas('arInvoice', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            })
            ->count();

        return Stat::make('Overdue Installments', $count)
            ->description('Installments past due date')
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color('danger');
    }

    protected function getArOpenAmountStat(?int $branchId): Stat
    {
        $amount = ArInvoice::whereIn('status', ['open', 'partial'])
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->get()
            ->sum('due_amount');

        return Stat::make('AR Open Amount', number_format($amount, 2))
            ->description('Total accounts receivable open')
            ->descriptionIcon('heroicon-m-document-text')
            ->color('warning');
    }

    protected function getRecognizedRevenueThisMonthStat(?int $branchId): Stat
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $amount = RevenueRecognition::whereBetween('recognized_at', [$startOfMonth, $endOfMonth])
            ->when($branchId, function ($query) use ($branchId) {
                return $query->whereHas('enrollment', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            })
            ->sum('recognized_amount');

        return Stat::make('Recognized Revenue This Month', number_format($amount, 2))
            ->description('Revenue recognized this month')
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color('success');
    }
}

