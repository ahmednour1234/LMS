<?php

namespace App\Filament\Admin\Widgets;

use App\Domain\Accounting\Models\ArInstallment;
use App\Domain\Accounting\Models\ArInvoice;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\RevenueRecognition;
use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Enrollment\Models\Student;
use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Teacher;
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
            $this->getTotalCoursesStat($branchId),
            $this->getTotalStudentsStat($branchId),
            $this->getTotalTeachersStat($branchId),
        ];
    }

    protected function getEnrollmentsTodayStat(?int $branchId): Stat
    {
        $count = Enrollment::whereDate('created_at', today())
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();

        return Stat::make(__('dashboard.stats.enrollments_today'), $count)
            ->description(__('dashboard.stats.enrollments_today_desc'))
            ->descriptionIcon('heroicon-m-user-plus')
            ->color('success');
    }

    protected function getPaymentsCollectedTodayStat(?int $branchId): Stat
    {
        $amount = Payment::where('status', 'completed')
            ->whereDate('paid_at', today())
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->sum('amount');

        return Stat::make(__('dashboard.stats.payments_collected_today'), number_format($amount, 2))
            ->description(__('dashboard.stats.payments_collected_today_desc'))
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

        return Stat::make(__('dashboard.stats.collections_this_month'), number_format($amount, 2))
            ->description(__('dashboard.stats.collections_this_month_desc'))
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

        return Stat::make(__('dashboard.stats.overdue_installments'), $count)
            ->description(__('dashboard.stats.overdue_installments_desc'))
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color('danger');
    }

    protected function getArOpenAmountStat(?int $branchId): Stat
    {
        $amount = ArInvoice::whereIn('status', ['open', 'partial'])
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->get()
            ->sum('due_amount');

        return Stat::make(__('dashboard.stats.ar_open_amount'), number_format($amount, 2))
            ->description(__('dashboard.stats.ar_open_amount_desc'))
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

        return Stat::make(__('dashboard.stats.recognized_revenue_this_month'), number_format($amount, 2))
            ->description(__('dashboard.stats.recognized_revenue_this_month_desc'))
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color('success');
    }

    protected function getTotalCoursesStat(?int $branchId): Stat
    {
        $count = Course::where('is_active', true)
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();

        return Stat::make(__('dashboard.stats.total_courses'), $count)
            ->description(__('dashboard.stats.active_courses'))
            ->descriptionIcon('heroicon-m-academic-cap')
            ->color('primary');
    }

    protected function getTotalStudentsStat(?int $branchId): Stat
    {
        $count = Student::where('active', true)
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();

        return Stat::make(__('dashboard.stats.total_students'), $count)
            ->description(__('dashboard.stats.active_students'))
            ->descriptionIcon('heroicon-m-user-group')
            ->color('info');
    }

    protected function getTotalTeachersStat(?int $branchId): Stat
    {
        $count = Teacher::where('active', true)
            ->count();

        return Stat::make(__('dashboard.stats.total_teachers'), $count)
            ->description(__('dashboard.stats.active_teachers'))
            ->descriptionIcon('heroicon-m-user-circle')
            ->color('warning');
    }
}

