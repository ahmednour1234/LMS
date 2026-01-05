<?php

namespace App\Filament\Admin\Widgets;

use App\Domain\Accounting\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CollectionsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Collections Last 30 Days';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function getData(): array
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $payments = Payment::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Create a complete date range
        $dates = [];
        $amounts = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('M d');
            
            $payment = $payments->firstWhere('date', $dateKey);
            $amounts[] = $payment ? (float) $payment->total : 0;
            
            $currentDate->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Collections',
                    'data' => $amounts,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
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
                        'callback' => 'function(value) { return "$" + value.toLocaleString(); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "$" + context.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
}

