<?php

namespace App\Filament\Admin\Widgets;

use App\Domain\Enrollment\Models\Enrollment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class EnrollmentsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Enrollments Last 30 Days';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function getData(): array
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $enrollments = Enrollment::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Create a complete date range
        $dates = [];
        $counts = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('M d');
            
            $enrollment = $enrollments->firstWhere('date', $dateKey);
            $counts[] = $enrollment ? (int) $enrollment->count : 0;
            
            $currentDate->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Enrollments',
                    'data' => $counts,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}

