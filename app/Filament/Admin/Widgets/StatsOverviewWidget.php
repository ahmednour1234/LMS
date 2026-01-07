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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        return [
            $this->statEnrollmentsToday($branchId),
            $this->statPaymentsCollectedToday($branchId),
            $this->statCollectionsThisMonth($branchId),
            $this->statOverdueInstallments($branchId),
            $this->statArOpenAmount($branchId),
            $this->statRecognizedRevenueThisMonth($branchId),
            $this->statTotalCourses($branchId),
            $this->statTotalStudents($branchId),
            $this->statTotalTeachers($branchId),
        ];
    }

    /* =========================
     |  STATS
     * ========================= */

    protected function statEnrollmentsToday(?int $branchId): Stat
    {
        $today = today();

        $countToday = Enrollment::query()
            ->whereDate('created_at', $today)
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->count();

        $chart = $this->lastDaysChart(7, function (Carbon $date) use ($branchId) {
            return Enrollment::query()
                ->whereDate('created_at', $date)
                ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->count();
        });

        $trend = $this->trendVsPreviousDay($chart);

        return $this->makeStat(
            label: __('dashboard.stats.enrollments_today') ?? 'Enrollments Today',
            value: $countToday,
            icon: 'heroicon-o-academic-cap',
            color: 'success',
            chart: $chart,
            chartColor: 'success',
            description: $this->withTrendPrefix(
                $trend,
                __('dashboard.stats.enrollments_today_desc') ?? 'New enrollments registered today'
            ),
            descriptionIcon: 'heroicon-m-user-plus'
        );
    }

    protected function statPaymentsCollectedToday(?int $branchId): Stat
    {
        $today = today();

        $amountToday = (float) Payment::query()
            ->where('status', 'completed')
            ->whereDate('paid_at', $today)
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $chart = $this->lastDaysChart(7, function (Carbon $date) use ($branchId) {
            return (float) Payment::query()
                ->where('status', 'completed')
                ->whereDate('paid_at', $date)
                ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->sum('amount');
        });

        $trend = $this->trendVsPreviousDay($chart);

        return $this->makeStat(
            label: __('dashboard.stats.payments_collected_today') ?? 'Payments Today',
            value: $this->money($amountToday),
            icon: 'heroicon-o-banknotes',
            color: $trend['is_positive'] ? 'success' : 'danger',
            chart: $chart,
            chartColor: 'success',
            description: $this->withTrendPrefix(
                $trend,
                __('dashboard.stats.payments_collected_today_desc') ?? 'Total payments collected today'
            ),
            descriptionIcon: 'heroicon-m-currency-dollar'
        );
    }

    protected function statCollectionsThisMonth(?int $branchId): Stat
    {
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        $amount = (float) Payment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$start, $end])
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $lastStart = now()->subMonth()->startOfMonth();
        $lastEnd   = now()->subMonth()->endOfMonth();

        $lastAmount = (float) Payment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$lastStart, $lastEnd])
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        // Weekly-ish chart inside current month (7 points)
        $chart = $this->monthBucketsChart($start, $end, 7, function (Carbon $from, Carbon $to) use ($branchId) {
            return (float) Payment::query()
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$from, $to])
                ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->sum('amount');
        });

        $trend = $this->trendPercent($amount, $lastAmount);

        return $this->makeStat(
            label: __('dashboard.stats.collections_this_month') ?? 'Collections This Month',
            value: $this->money($amount),
            icon: 'heroicon-o-chart-bar-square',
            color: 'info',
            chart: $chart,
            chartColor: 'info',
            description: $this->withTrendPrefix(
                $trend,
                __('dashboard.stats.collections_this_month_desc') ?? 'Total collections for current month',
                suffix: ' vs last month'
            ),
            descriptionIcon: 'heroicon-m-banknotes'
        );
    }

    protected function statOverdueInstallments(?int $branchId): Stat
    {
        $count = ArInstallment::query()
            ->where('due_date', '<', today())
            ->where('status', '!=', 'paid')
            ->when($branchId, function (Builder $q) use ($branchId) {
                return $q->whereHas('arInvoice', fn (Builder $inv) => $inv->where('branch_id', $branchId));
            })
            ->count();

        // trend chart: "how many overdue as of each day"
        $chart = $this->lastDaysChart(7, function (Carbon $date) use ($branchId) {
            return ArInstallment::query()
                ->where('due_date', '<', $date)
                ->where('status', '!=', 'paid')
                ->when($branchId, function (Builder $q) use ($branchId) {
                    return $q->whereHas('arInvoice', fn (Builder $inv) => $inv->where('branch_id', $branchId));
                })
                ->count();
        });

        return $this->makeStat(
            label: __('dashboard.stats.overdue_installments') ?? 'Overdue Installments',
            value: $count,
            icon: 'heroicon-o-exclamation-circle',
            color: 'danger',
            chart: $chart,
            chartColor: 'danger',
            description: __('dashboard.stats.overdue_installments_desc') ?? 'Installments requiring attention',
            descriptionIcon: 'heroicon-m-exclamation-triangle'
        );
    }

    protected function statArOpenAmount(?int $branchId): Stat
    {
        // NOTE: using sum in DB is better if due_amount is stored column.
        // If due_amount is accessor, keep ->get()->sum(...) as you had.
        $openQuery = ArInvoice::query()
            ->whereIn('status', ['open', 'partial'])
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));

        $invoiceCount = (clone $openQuery)->count();
        $amount = (float) (clone $openQuery)->get()->sum('due_amount');

        $chart = $this->lastDaysChart(7, function (Carbon $date) use ($branchId) {
            return (float) ArInvoice::query()
                ->whereIn('status', ['open', 'partial'])
                ->whereDate('created_at', '<=', $date)
                ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->get()
                ->sum('due_amount');
        });

        $desc = ($invoiceCount > 0 ? "{$invoiceCount} invoices • " : '')
            . (__('dashboard.stats.ar_open_amount_desc') ?? 'Accounts receivable outstanding');

        return $this->makeStat(
            label: __('dashboard.stats.ar_open_amount') ?? 'AR Open Amount',
            value: $this->money($amount),
            icon: 'heroicon-o-document-duplicate',
            color: 'warning',
            chart: $chart,
            chartColor: 'warning',
            description: $desc,
            descriptionIcon: 'heroicon-m-document-text'
        );
    }

    protected function statRecognizedRevenueThisMonth(?int $branchId): Stat
    {
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        $amount = (float) RevenueRecognition::query()
            ->whereBetween('recognized_at', [$start, $end])
            ->when($branchId, function (Builder $q) use ($branchId) {
                return $q->whereHas('enrollment', fn (Builder $enr) => $enr->where('branch_id', $branchId));
            })
            ->sum('recognized_amount');

        $lastStart = now()->subMonth()->startOfMonth();
        $lastEnd   = now()->subMonth()->endOfMonth();

        $lastAmount = (float) RevenueRecognition::query()
            ->whereBetween('recognized_at', [$lastStart, $lastEnd])
            ->when($branchId, function (Builder $q) use ($branchId) {
                return $q->whereHas('enrollment', fn (Builder $enr) => $enr->where('branch_id', $branchId));
            })
            ->sum('recognized_amount');

        $chart = $this->lastDaysChart(7, function (Carbon $date) use ($branchId) {
            return (float) RevenueRecognition::query()
                ->whereDate('recognized_at', $date)
                ->when($branchId, function (Builder $q) use ($branchId) {
                    return $q->whereHas('enrollment', fn (Builder $enr) => $enr->where('branch_id', $branchId));
                })
                ->sum('recognized_amount');
        });

        $trend = $this->trendPercent($amount, $lastAmount);

        return $this->makeStat(
            label: __('dashboard.stats.recognized_revenue_this_month') ?? 'Recognized Revenue',
            value: $this->money($amount),
            icon: 'heroicon-o-chart-bar',
            color: 'success',
            chart: $chart,
            chartColor: 'success',
            description: $this->withTrendPrefix(
                $trend,
                __('dashboard.stats.recognized_revenue_this_month_desc') ?? 'Revenue recognized this month',
                suffix: ' vs last month'
            ),
            descriptionIcon: 'heroicon-m-chart-bar'
        );
    }

    protected function statTotalCourses(?int $branchId): Stat
    {
        $activeQuery = Course::query()
            ->where('is_active', true)
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));

        $count = (clone $activeQuery)->count();
        $totalCount = Course::query()
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->count();

        $newThisMonth = Course::query()
            ->where('is_active', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->count();

        // chart: cumulative active courses as-of day (kept close to your original)
        $chart = $this->lastDaysChart(7, function (Carbon $date) use ($branchId) {
            return Course::query()
                ->where('is_active', true)
                ->whereDate('created_at', '<=', $date)
                ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->count();
        });

        $desc = ($newThisMonth > 0 ? "{$newThisMonth} new this month • " : '')
            . (__('dashboard.stats.active_courses') ?? "{$count} of {$totalCount} total courses");

        return $this->makeStat(
            label: __('dashboard.stats.total_courses') ?? 'Active Courses',
            value: $count,
            icon: 'heroicon-o-book-open',
            color: 'primary',
            chart: $chart,
            chartColor: 'primary',
            description: $desc,
            descriptionIcon: 'heroicon-m-academic-cap'
        );
    }

    protected function statTotalStudents(?int $branchId): Stat
    {
        $count = Student::query()
            ->where('status', 'active')
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->count();

        $newThisMonth = Student::query()
            ->where('status', 'active')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->count();

        $chart = $this->lastDaysChart(7, function (Carbon $date) use ($branchId) {
            return Student::query()
                ->where('status', 'active')
                ->whereDate('created_at', '<=', $date)
                ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->count();
        });

        $desc = ($newThisMonth > 0 ? "{$newThisMonth} new this month • " : '')
            . (__('dashboard.stats.active_students') ?? 'Total active students');

        return $this->makeStat(
            label: __('dashboard.stats.total_students') ?? 'Active Students',
            value: $count,
            icon: 'heroicon-o-users',
            color: 'info',
            chart: $chart,
            chartColor: 'info',
            description: $desc,
            descriptionIcon: 'heroicon-m-user-group'
        );
    }

    protected function statTotalTeachers(?int $branchId): Stat
    {
        // branch filter not applied in original; keeping same behavior.
        $count = Teacher::query()->where('active', true)->count();
        $total = Teacher::query()->count();

        $newThisMonth = Teacher::query()
            ->where('active', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $chart = $this->lastDaysChart(7, function (Carbon $date) {
            return Teacher::query()
                ->where('active', true)
                ->whereDate('created_at', '<=', $date)
                ->count();
        });

        $desc = ($newThisMonth > 0 ? "{$newThisMonth} new this month • " : '')
            . (__('dashboard.stats.active_teachers') ?? "{$count} of {$total} total teachers");

        return $this->makeStat(
            label: __('dashboard.stats.total_teachers') ?? 'Active Teachers',
            value: $count,
            icon: 'heroicon-o-academic-cap',
            color: 'warning',
            chart: $chart,
            chartColor: 'warning',
            description: $desc,
            descriptionIcon: 'heroicon-m-user-circle'
        );
    }

    /* =========================
     |  HELPERS
     * ========================= */

    protected function makeStat(
        string $label,
        mixed $value,
        string $icon,
        string $color,
        array $chart = [],
        string $chartColor = 'primary',
        ?string $description = null,
        ?string $descriptionIcon = null,
        ?string $descriptionColor = null
    ): Stat {
        $stat = Stat::make($label, $value)
            ->icon($icon)
            ->color($color)
            ->extraAttributes($this->cardAttributes());

        if ($description !== null) {
            $stat->description($description);
        }

        if ($descriptionIcon !== null) {
            $stat->descriptionIcon($descriptionIcon);
        }

        if ($descriptionColor !== null) {
            $stat->descriptionColor($descriptionColor);
        }

        if (!empty($chart)) {
            $stat->chart($chart)->chartColor($chartColor);
        }

        return $stat;
    }

    protected function cardAttributes(): array
    {
        return [
            'class' => 'cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all duration-300 rounded-xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900',
        ];
    }

    /**
     * Generate chart data for last N days (including today).
     */
    protected function lastDaysChart(int $days, callable $callback): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $data[] = $callback($date);
        }
        return $data;
    }

    /**
     * Build chart buckets across a month (or any range) into fixed points.
     * Ensures chart size = $points.
     */
    protected function monthBucketsChart(Carbon $start, Carbon $end, int $points, callable $callback): array
    {
        $days = max(1, $start->diffInDays($end));
        $bucketSize = (int) ceil($days / $points);

        $data = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $points; $i++) {
            $from = $cursor->copy();
            $to = min($cursor->copy()->addDays($bucketSize - 1), $end);

            $data[] = $callback($from, $to);

            $cursor = $to->copy()->addDay();
            if ($cursor->gt($end)) {
                // pad remaining with zeros
                for ($j = $i + 1; $j < $points; $j++) {
                    $data[] = 0;
                }
                break;
            }
        }

        return $data;
    }

    /**
     * Trend vs "yesterday" based on a 7-day chart:
     * last point = today, previous point = yesterday.
     */
    protected function trendVsPreviousDay(array $chart): array
    {
        $today = (float) ($chart[count($chart) - 1] ?? 0);
        $yesterday = (float) ($chart[count($chart) - 2] ?? 0);

        return $this->trendPercent($today, $yesterday);
    }

    protected function trendPercent(float $current, float $previous): array
    {
        if ($previous <= 0) {
            return [
                'percent' => 0.0,
                'text' => '',
                'is_positive' => true,
                'color' => 'success',
            ];
        }

        $percent = round((($current - $previous) / $previous) * 100, 1);
        $isPositive = $percent >= 0;

        return [
            'percent' => $percent,
            'text' => ($isPositive ? '+' : '') . $percent . '%',
            'is_positive' => $isPositive,
            'color' => $isPositive ? 'success' : 'danger',
        ];
    }

    protected function withTrendPrefix(array $trend, string $message, string $suffix = ' from yesterday'): string
    {
        if (($trend['percent'] ?? 0) == 0) {
            return $message;
        }

        $text = $trend['text'] ?? '';
        return "{$text}{$suffix} • {$message}";
    }

    protected function money(float $amount): string
    {
        return number_format($amount, 2) . ' OMR';
    }
}
