<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\CollectionsChartWidget;
use App\Filament\Admin\Widgets\EnrollmentsChartWidget;
use App\Filament\Admin\Widgets\LatestEnrollmentsTableWidget;
use App\Filament\Admin\Widgets\LatestPaymentsTableWidget;
use App\Filament\Admin\Widgets\OverdueInstallmentsTableWidget;
use App\Filament\Admin\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            CollectionsChartWidget::class,
            EnrollmentsChartWidget::class,
            LatestPaymentsTableWidget::class,
            LatestEnrollmentsTableWidget::class,
            OverdueInstallmentsTableWidget::class,
        ];
    }
}

