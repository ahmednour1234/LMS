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
        return $this->buildStat([
            'label' => __('dashboard.stats.enrollments_today') ?? 'Enrollments Today',
            'value_callback' => fn(?int $bid) => Enrollment::query()
                ->whereDate('created_at', today())
                ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                ->count(),
            'icon' => 'heroicon-o-academic-cap',
            'color' => 'success',
            'chart' => [
                'type' => 'last_days',
                'days' => 7,
                'callback' => fn(Carbon $date, ?int $bid) => Enrollment::query()
                    ->whereDate('created_at', $date)
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->count(),
            ],
            'trend' => [
                'type' => 'vs_previous_day',
                'suffix' => ' from yesterday',
            ],
            'description' => __('dashboard.stats.enrollments_today_desc') ?? 'New enrollments registered today',
            'description_icon' => 'heroicon-m-user-plus',
            'chart_color' => 'success',
        ], $branchId);
    }

    protected function statPaymentsCollectedToday(?int $branchId): Stat
    {
        return $this->buildStat([
            'label' => __('dashboard.stats.payments_collected_today') ?? 'Payments Today',
            'value_callback' => fn(?int $bid) => (float) Payment::query()
                ->where('status', 'completed')
                ->whereDate('paid_at', today())
                ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                ->sum('amount'),
            'value_formatter' => fn($value) => $this->money($value),
            'icon' => 'heroicon-o-banknotes',
            'color' => fn(array $trend) => $trend['is_positive'] ? 'success' : 'danger',
            'chart' => [
                'type' => 'last_days',
                'days' => 7,
                'callback' => fn(Carbon $date, ?int $bid) => (float) Payment::query()
                    ->where('status', 'completed')
                    ->whereDate('paid_at', $date)
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->sum('amount'),
            ],
            'trend' => [
                'type' => 'vs_previous_day',
                'suffix' => ' from yesterday',
            ],
            'description' => __('dashboard.stats.payments_collected_today_desc') ?? 'Total payments collected today',
            'description_icon' => 'heroicon-m-currency-dollar',
            'chart_color' => 'success',
        ], $branchId);
    }

    protected function statCollectionsThisMonth(?int $branchId): Stat
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return $this->buildStat([
            'label' => __('dashboard.stats.collections_this_month') ?? 'Collections This Month',
            'value_callback' => fn(?int $bid) => (float) Payment::query()
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$start, $end])
                ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                ->sum('amount'),
            'value_formatter' => fn($value) => $this->money($value),
            'icon' => 'heroicon-o-chart-bar-square',
            'color' => 'info',
            'chart' => [
                'type' => 'month_buckets',
                'start' => $start,
                'end' => $end,
                'points' => 7,
                'callback' => fn(Carbon $from, Carbon $to, ?int $bid) => (float) Payment::query()
                    ->where('status', 'completed')
                    ->whereBetween('paid_at', [$from, $to])
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->sum('amount'),
            ],
            'trend' => [
                'type' => 'vs_previous_period',
                'comparison_callback' => fn(?int $bid) => (float) Payment::query()
                    ->where('status', 'completed')
                    ->whereBetween('paid_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->sum('amount'),
                'suffix' => ' vs last month',
            ],
            'description' => __('dashboard.stats.collections_this_month_desc') ?? 'Total collections for current month',
            'description_icon' => 'heroicon-m-banknotes',
            'chart_color' => 'info',
        ], $branchId);
    }

    protected function statOverdueInstallments(?int $branchId): Stat
    {
        return $this->buildStat([
            'label' => __('dashboard.stats.overdue_installments') ?? 'Overdue Installments',
            'value_callback' => fn(?int $bid) => ArInstallment::query()
                ->where('due_date', '<', today())
                ->where('status', '!=', 'paid')
                ->when($bid, function (Builder $q) use ($bid) {
                    return $q->whereHas('arInvoice', fn (Builder $inv) => $inv->where('branch_id', $bid));
                })
                ->count(),
            'icon' => 'heroicon-o-exclamation-circle',
            'color' => 'danger',
            'chart' => [
                'type' => 'last_days',
                'days' => 7,
                'callback' => fn(Carbon $date, ?int $bid) => ArInstallment::query()
                    ->where('due_date', '<', $date)
                    ->where('status', '!=', 'paid')
                    ->when($bid, function (Builder $q) use ($bid) {
                        return $q->whereHas('arInvoice', fn (Builder $inv) => $inv->where('branch_id', $bid));
                    })
                    ->count(),
            ],
            'trend' => null,
            'description' => __('dashboard.stats.overdue_installments_desc') ?? 'Installments requiring attention',
            'description_icon' => 'heroicon-m-exclamation-triangle',
            'chart_color' => 'danger',
        ], $branchId);
    }

    protected function statArOpenAmount(?int $branchId): Stat
    {
        return $this->buildStat([
            'label' => __('dashboard.stats.ar_open_amount') ?? 'AR Open Amount',
            'value_callback' => function(?int $bid) {
                $openQuery = ArInvoice::query()
                    ->whereIn('status', ['open', 'partial'])
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid));
                return (float) (clone $openQuery)->get()->sum('due_amount');
            },
            'value_formatter' => fn($value) => $this->money($value),
            'icon' => 'heroicon-o-document-duplicate',
            'color' => 'warning',
            'chart' => [
                'type' => 'last_days',
                'days' => 7,
                'callback' => fn(Carbon $date, ?int $bid) => (float) ArInvoice::query()
                    ->whereIn('status', ['open', 'partial'])
                    ->whereDate('created_at', '<=', $date)
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->get()
                    ->sum('due_amount'),
            ],
            'trend' => null,
            'description' => function($trend, $value, ?int $bid) {
                $openQuery = ArInvoice::query()
                    ->whereIn('status', ['open', 'partial'])
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid));
                $invoiceCount = (clone $openQuery)->count();
                return ($invoiceCount > 0 ? "{$invoiceCount} invoices • " : '')
                    . (__('dashboard.stats.ar_open_amount_desc') ?? 'Accounts receivable outstanding');
            },
            'description_icon' => 'heroicon-m-document-text',
            'chart_color' => 'warning',
        ], $branchId);
    }

    protected function statRecognizedRevenueThisMonth(?int $branchId): Stat
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return $this->buildStat([
            'label' => __('dashboard.stats.recognized_revenue_this_month') ?? 'Recognized Revenue',
            'value_callback' => fn(?int $bid) => (float) RevenueRecognition::query()
                ->whereBetween('recognized_at', [$start, $end])
                ->when($bid, function (Builder $q) use ($bid) {
                    return $q->whereHas('enrollment', fn (Builder $enr) => $enr->where('branch_id', $bid));
                })
                ->sum('recognized_amount'),
            'value_formatter' => fn($value) => $this->money($value),
            'icon' => 'heroicon-o-chart-bar',
            'color' => 'success',
            'chart' => [
                'type' => 'last_days',
                'days' => 7,
                'callback' => fn(Carbon $date, ?int $bid) => (float) RevenueRecognition::query()
                    ->whereDate('recognized_at', $date)
                    ->when($bid, function (Builder $q) use ($bid) {
                        return $q->whereHas('enrollment', fn (Builder $enr) => $enr->where('branch_id', $bid));
                    })
                    ->sum('recognized_amount'),
            ],
            'trend' => [
                'type' => 'vs_previous_period',
                'comparison_callback' => fn(?int $bid) => (float) RevenueRecognition::query()
                    ->whereBetween('recognized_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
                    ->when($bid, function (Builder $q) use ($bid) {
                        return $q->whereHas('enrollment', fn (Builder $enr) => $enr->where('branch_id', $bid));
                    })
                    ->sum('recognized_amount'),
                'suffix' => ' vs last month',
            ],
            'description' => __('dashboard.stats.recognized_revenue_this_month_desc') ?? 'Revenue recognized this month',
            'description_icon' => 'heroicon-m-chart-bar',
            'chart_color' => 'success',
        ], $branchId);
    }

    protected function statTotalCourses(?int $branchId): Stat
    {
        return $this->buildStat([
            'label' => __('dashboard.stats.total_courses') ?? 'Active Courses',
            'value_callback' => function(?int $bid) {
                $activeQuery = Course::query()
                    ->where('is_active', true)
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid));
                return (clone $activeQuery)->count();
            },
            'icon' => 'heroicon-o-book-open',
            'color' => 'primary',
            'chart' => [
                'type' => 'last_days',
                'days' => 7,
                'callback' => fn(Carbon $date, ?int $bid) => Course::query()
                    ->where('is_active', true)
                    ->whereDate('created_at', '<=', $date)
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->count(),
            ],
            'trend' => null,
            'description' => function($trend, $value, ?int $bid) {
                $activeQuery = Course::query()
                    ->where('is_active', true)
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid));
                $count = (clone $activeQuery)->count();
                $totalCount = Course::query()
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->count();
                $newThisMonth = Course::query()
                    ->where('is_active', true)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->count();
                return ($newThisMonth > 0 ? "{$newThisMonth} new this month • " : '')
                    . (__('dashboard.stats.active_courses') ?? "{$count} of {$totalCount} total courses");
            },
            'description_icon' => 'heroicon-m-academic-cap',
            'chart_color' => 'primary',
        ], $branchId);
    }

    protected function statTotalStudents(?int $branchId): Stat
    {
        return $this->buildStat([
            'label' => __('dashboard.stats.total_students') ?? 'Active Students',
            'value_callback' => fn(?int $bid) => Student::query()
                ->where('status', 'active')
                ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                ->count(),
            'icon' => 'heroicon-o-users',
            'color' => 'info',
            'chart' => [
                'type' => 'last_days',
                'days' => 7,
                'callback' => fn(Carbon $date, ?int $bid) => Student::query()
                    ->where('status', 'active')
                    ->whereDate('created_at', '<=', $date)
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->count(),
            ],
            'trend' => null,
            'description' => function($trend, $value, ?int $bid) {
                $count = Student::query()
                    ->where('status', 'active')
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->count();
                $newThisMonth = Student::query()
                    ->where('status', 'active')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->when($bid, fn (Builder $q) => $q->where('branch_id', $bid))
                    ->count();
                return ($newThisMonth > 0 ? "{$newThisMonth} new this month • " : '')
                    . (__('dashboard.stats.active_students') ?? 'Total active students');
            },
            'description_icon' => 'heroicon-m-user-group',
            'chart_color' => 'info',
        ], $branchId);
    }

    protected function statTotalTeachers(?int $branchId): Stat
    {
        return $this->buildStat([
            'label' => __('dashboard.stats.total_teachers') ?? 'Active Teachers',
            'value_callback' => fn(?int $bid) => Teacher::query()->where('active', true)->count(),
            'icon' => 'heroicon-o-academic-cap',
            'color' => 'warning',
            'chart' => [
                'type' => 'last_days',
                'days' => 7,
                'callback' => fn(Carbon $date, ?int $bid) => Teacher::query()
                    ->where('active', true)
                    ->whereDate('created_at', '<=', $date)
                    ->count(),
            ],
            'trend' => null,
            'description' => function($trend, $value, ?int $bid) {
                $count = Teacher::query()->where('active', true)->count();
                $total = Teacher::query()->count();
                $newThisMonth = Teacher::query()
                    ->where('active', true)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
                return ($newThisMonth > 0 ? "{$newThisMonth} new this month • " : '')
                    . (__('dashboard.stats.active_teachers') ?? "{$count} of {$total} total teachers");
            },
            'description_icon' => 'heroicon-m-user-circle',
            'chart_color' => 'warning',
        ], $branchId);
    }

    /* =========================
     |  HELPERS
     * ========================= */

    /**
     * Build a stat from configuration array.
     * 
     * @param array $config Configuration with:
     *   - 'label': Stat title (string)
     *   - 'value_callback': Closure(?int $branchId): mixed
     *   - 'value_formatter': Closure(mixed): string|null (optional, e.g., money formatter)
     *   - 'icon': Icon name (string)
     *   - 'color': Color string or Closure(array $trend): string
     *   - 'chart': [
     *       'type' => 'last_days'|'month_buckets',
     *       'callback' => Closure(Carbon $date) or Closure(Carbon $from, Carbon $to),
     *       'days' => int (for last_days),
     *       'start' => Carbon, 'end' => Carbon, 'points' => int (for month_buckets)
     *     ]|null
     *   - 'trend': [
     *       'type' => 'vs_previous_day'|'vs_previous_period'|null,
     *       'comparison_callback' => Closure(?int $branchId): float|null (for vs_previous_period),
     *       'suffix' => string (optional, default: ' from yesterday')
     *     ]|null
     *   - 'description': string|Closure(array|null $trend, mixed $value, ?int $branchId): string
     *   - 'description_icon': string|null
     *   - 'chart_color': string (optional, defaults to color)
     */
    protected function buildStat(array $config, ?int $branchId): Stat
    {
        // Calculate current value
        $rawValue = ($config['value_callback'])($branchId);
        
        // Format value if formatter provided
        $value = isset($config['value_formatter']) 
            ? ($config['value_formatter'])($rawValue) 
            : $rawValue;

        // Generate chart
        $chart = [];
        if (isset($config['chart'])) {
            $chartConfig = $config['chart'];
            if ($chartConfig['type'] === 'last_days') {
                $chart = $this->lastDaysChart(
                    $chartConfig['days'],
                    fn (Carbon $date) => ($chartConfig['callback'])($date, $branchId)
                );
            } elseif ($chartConfig['type'] === 'month_buckets') {
                $chart = $this->monthBucketsChart(
                    $chartConfig['start'],
                    $chartConfig['end'],
                    $chartConfig['points'],
                    fn (Carbon $from, Carbon $to) => ($chartConfig['callback'])($from, $to, $branchId)
                );
            }
        }

        // Calculate trend
        $trend = null;
        if (isset($config['trend']) && $config['trend'] !== null) {
            $trendConfig = $config['trend'];
            if ($trendConfig['type'] === 'vs_previous_day') {
                $trend = $this->trendVsPreviousDay($chart);
            } elseif ($trendConfig['type'] === 'vs_previous_period') {
                $comparisonValue = isset($trendConfig['comparison_callback'])
                    ? ($trendConfig['comparison_callback'])($branchId)
                    : 0;
                $trend = $this->trendPercent((float)$rawValue, (float)$comparisonValue);
            }
        }

        // Determine color (can be dynamic based on trend)
        $color = $config['color'] ?? 'primary';
        if (is_callable($color)) {
            // Only execute callable if trend is available (callable likely depends on trend)
            $color = $trend !== null ? $color($trend) : 'primary';
        }
        // Ensure color is always a string and never null
        $color = (string) ($color ?? 'primary');

        // Build description
        $description = null;
        if (isset($config['description'])) {
            if (is_callable($config['description'])) {
                $description = ($config['description'])($trend, $rawValue, $branchId);
            } else {
                $descriptionStr = $config['description'];
                // Apply trend prefix if trend exists and description is a string
                if ($trend !== null && isset($config['trend'])) {
                    $suffix = $config['trend']['suffix'] ?? ' from yesterday';
                    $description = $this->withTrendPrefix($trend, $descriptionStr, $suffix);
                } else {
                    $description = $descriptionStr;
                }
            }
        }

        // Chart color defaults to stat color (ensure it's always a string)
        $chartColor = (string) ($config['chart_color'] ?? $color);

        return $this->makeStat(
            label: $config['label'],
            value: $value,
            icon: $config['icon'],
            color: $color,
            chart: $chart,
            chartColor: $chartColor,
            description: $description,
            descriptionIcon: $config['description_icon'] ?? null
        );
    }

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
