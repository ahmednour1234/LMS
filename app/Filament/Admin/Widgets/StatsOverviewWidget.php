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
        
        // Get last 7 days for chart
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $chartData[] = Enrollment::whereDate('created_at', $date)
                ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                ->count();
        }
        
        $previousCount = $chartData[5] ?? 0;
        $trend = $previousCount > 0 ? round((($count - $previousCount) / $previousCount * 100), 1) : 0;
        $trendIcon = $trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendColor = $trend >= 0 ? 'success' : 'danger';
        $trendText = $trend >= 0 ? "+{$trend}%" : "{$trend}%";

        return Stat::make(__('dashboard.stats.enrollments_today') ?? 'Enrollments Today', $count)
            ->description(
                ($trend != 0 ? "{$trendText} from yesterday • " : '') . 
                __('dashboard.stats.enrollments_today_desc') ?? 'New enrollments registered today'
            )
            ->descriptionIcon('heroicon-m-user-plus')
            ->descriptionColor($trendColor)
            ->color('success')
            ->icon('heroicon-o-academic-cap')
            ->chart($chartData)
            ->chartColor('success')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
            ]);
    }

    protected function getPaymentsCollectedTodayStat(?int $branchId): Stat
    {
        $amount = Payment::where('status', 'completed')
            ->whereDate('paid_at', today())
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->sum('amount');
        
        // Get last 7 days for chart
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dailyAmount = Payment::where('status', 'completed')
                ->whereDate('paid_at', $date)
                ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                ->sum('amount');
            $chartData[] = (float) $dailyAmount;
        }
        
        $previousAmount = $chartData[5] ?? 0;
        $trend = $previousAmount > 0 ? round((($amount - $previousAmount) / $previousAmount * 100), 1) : 0;
        $trendText = $trend >= 0 ? "+{$trend}%" : "{$trend}%";

        return Stat::make(__('dashboard.stats.payments_collected_today') ?? 'Payments Today', number_format($amount, 2) . ' OMR')
            ->description(
                ($trend != 0 ? "{$trendText} from yesterday • " : '') . 
                __('dashboard.stats.payments_collected_today_desc') ?? 'Total payments collected today'
            )
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->descriptionColor($trend >= 0 ? 'success' : 'danger')
            ->color('success')
            ->icon('heroicon-o-banknotes')
            ->chart($chartData)
            ->chartColor('success')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
            ]);
    }

    protected function getCollectionsThisMonthStat(?int $branchId): Stat
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $amount = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->sum('amount');
        
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $lastMonthAmount = Payment::where('status', 'completed')
            ->whereBetween('paid_at', [$lastMonthStart, $lastMonthEnd])
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->sum('amount');
        
        // Generate chart data for this month (weekly data)
        $chartData = [];
        $weeksInMonth = 4;
        $daysPerWeek = (int) ceil($startOfMonth->diffInDays($endOfMonth) / $weeksInMonth);
        for ($i = 0; $i < $weeksInMonth; $i++) {
            $weekStart = $startOfMonth->copy()->addDays($i * $daysPerWeek);
            $weekEnd = min($weekStart->copy()->addDays($daysPerWeek - 1), $endOfMonth);
            $weekAmount = Payment::where('status', 'completed')
                ->whereBetween('paid_at', [$weekStart, $weekEnd])
                ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                ->sum('amount');
            $chartData[] = (float) $weekAmount;
        }
        if (count($chartData) < 7) {
            // Fill to 7 data points for better chart
            $chartData = array_merge(array_fill(0, 7 - count($chartData), 0), $chartData);
        }
        
        $trend = $lastMonthAmount > 0 ? round((($amount - $lastMonthAmount) / $lastMonthAmount * 100), 1) : 0;
        $trendText = $trend >= 0 ? "+{$trend}%" : "{$trend}%";

        return Stat::make(__('dashboard.stats.collections_this_month') ?? 'Collections This Month', number_format($amount, 2) . ' OMR')
            ->description(
                ($trend != 0 ? "{$trendText} vs last month • " : '') . 
                __('dashboard.stats.collections_this_month_desc') ?? 'Total collections for current month'
            )
            ->descriptionIcon('heroicon-m-banknotes')
            ->descriptionColor($trend >= 0 ? 'success' : 'danger')
            ->color('info')
            ->icon('heroicon-o-chart-bar-square')
            ->chart($chartData)
            ->chartColor('info')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
            ]);
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
        
        // Get last 7 days for chart
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dayCount = ArInstallment::where('due_date', '<', $date)
                ->where('status', '!=', 'paid')
                ->when($branchId, function ($query) use ($branchId) {
                    return $query->whereHas('arInvoice', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    });
                })
                ->count();
            $chartData[] = $dayCount;
        }

        return Stat::make(__('dashboard.stats.overdue_installments') ?? 'Overdue Installments', $count)
            ->description(__('dashboard.stats.overdue_installments_desc') ?? 'Installments requiring attention')
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color('danger')
            ->icon('heroicon-o-exclamation-circle')
            ->chart($chartData)
            ->chartColor('danger')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
            ]);
    }

    protected function getArOpenAmountStat(?int $branchId): Stat
    {
        $amount = ArInvoice::whereIn('status', ['open', 'partial'])
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->get()
            ->sum('due_amount');
        
        $invoiceCount = ArInvoice::whereIn('status', ['open', 'partial'])
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();
        
        // Get last 7 days for chart
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dayAmount = ArInvoice::whereIn('status', ['open', 'partial'])
                ->whereDate('created_at', '<=', $date)
                ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                ->get()
                ->sum('due_amount');
            $chartData[] = (float) $dayAmount;
        }

        return Stat::make(__('dashboard.stats.ar_open_amount') ?? 'AR Open Amount', number_format($amount, 2) . ' OMR')
            ->description(
                ($invoiceCount > 0 ? "{$invoiceCount} invoices • " : '') . 
                (__('dashboard.stats.ar_open_amount_desc') ?? 'Accounts receivable outstanding')
            )
            ->descriptionIcon('heroicon-m-document-text')
            ->color('warning')
            ->icon('heroicon-o-document-duplicate')
            ->chart($chartData)
            ->chartColor('warning')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
            ]);
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
        
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $lastMonthAmount = RevenueRecognition::whereBetween('recognized_at', [$lastMonthStart, $lastMonthEnd])
            ->when($branchId, function ($query) use ($branchId) {
                return $query->whereHas('enrollment', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            })
            ->sum('recognized_amount');
        
        // Get last 7 days for chart
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dayAmount = RevenueRecognition::whereDate('recognized_at', $date)
                ->when($branchId, function ($query) use ($branchId) {
                    return $query->whereHas('enrollment', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    });
                })
                ->sum('recognized_amount');
            $chartData[] = (float) $dayAmount;
        }
        
        $trend = $lastMonthAmount > 0 ? round((($amount - $lastMonthAmount) / $lastMonthAmount * 100), 1) : 0;
        $trendText = $trend >= 0 ? "+{$trend}%" : "{$trend}%";

        return Stat::make(__('dashboard.stats.recognized_revenue_this_month') ?? 'Recognized Revenue', number_format($amount, 2) . ' OMR')
            ->description(
                ($trend != 0 ? "{$trendText} vs last month • " : '') . 
                (__('dashboard.stats.recognized_revenue_this_month_desc') ?? 'Revenue recognized this month')
            )
            ->descriptionIcon('heroicon-m-chart-bar')
            ->descriptionColor($trend >= 0 ? 'success' : 'danger')
            ->color('success')
            ->icon('heroicon-o-chart-bar')
            ->chart($chartData)
            ->chartColor('success')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
            ]);
    }

    protected function getTotalCoursesStat(?int $branchId): Stat
    {
        $count = Course::where('is_active', true)
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();
        
        $totalCount = Course::when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();
        
        $newThisMonth = Course::where('is_active', true)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();
        
        // Get last 7 days for chart
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dayCount = Course::where('is_active', true)
                ->whereDate('created_at', '<=', $date)
                ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                ->count();
            $chartData[] = $dayCount;
        }

        return Stat::make(__('dashboard.stats.total_courses') ?? 'Active Courses', $count)
            ->description(
                ($newThisMonth > 0 ? "{$newThisMonth} new this month • " : '') . 
                (__('dashboard.stats.active_courses') ?? "{$count} of {$totalCount} total courses")
            )
            ->descriptionIcon('heroicon-m-academic-cap')
            ->color('primary')
            ->icon('heroicon-o-book-open')
            ->chart($chartData)
            ->chartColor('primary')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
            ]);
    }

    protected function getTotalStudentsStat(?int $branchId): Stat
    {
        $count = Student::where('status', 'active')
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();
        
        $newThisMonth = Student::where('status', 'active')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->count();
        
        // Get last 7 days for chart
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dayCount = Student::where('status', 'active')
                ->whereDate('created_at', '<=', $date)
                ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                ->count();
            $chartData[] = $dayCount;
        }

        return Stat::make(__('dashboard.stats.total_students') ?? 'Active Students', $count)
            ->description(
                ($newThisMonth > 0 ? "{$newThisMonth} new this month • " : '') . 
                (__('dashboard.stats.active_students') ?? 'Total active students')
            )
            ->descriptionIcon('heroicon-m-user-group')
            ->color('info')
            ->icon('heroicon-o-users')
            ->chart($chartData)
            ->chartColor('info')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
            ]);
    }

    protected function getTotalTeachersStat(?int $branchId): Stat
    {
        $count = Teacher::where('active', true)
            ->count();
        
        $totalCount = Teacher::count();
        
        $newThisMonth = Teacher::where('active', true)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        // Get last 7 days for chart
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $dayCount = Teacher::where('active', true)
                ->whereDate('created_at', '<=', $date)
                ->count();
            $chartData[] = $dayCount;
        }

        return Stat::make(__('dashboard.stats.total_teachers') ?? 'Active Teachers', $count)
            ->description(
                ($newThisMonth > 0 ? "{$newThisMonth} new this month • " : '') . 
                (__('dashboard.stats.active_teachers') ?? "{$count} of {$totalCount} total teachers")
            )
            ->descriptionIcon('heroicon-m-user-circle')
            ->color('warning')
            ->icon('heroicon-o-academic-cap')
            ->chart($chartData)
            ->chartColor('warning')
            ->extraAttributes([
                'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
            ]);
    }
}

