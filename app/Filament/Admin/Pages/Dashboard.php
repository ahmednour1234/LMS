<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\CollectionsChartWidget;
use App\Filament\Admin\Widgets\EnrollmentsChartWidget;
use App\Filament\Admin\Widgets\LatestCoursesTableWidget;
use App\Filament\Admin\Widgets\LatestEnrollmentsTableWidget;
use App\Filament\Admin\Widgets\LatestPaymentsTableWidget;
use App\Filament\Admin\Widgets\LatestStudentsTableWidget;
use App\Filament\Admin\Widgets\LatestTeachersTableWidget;
use App\Filament\Admin\Widgets\OverdueInstallmentsTableWidget;
use App\Filament\Admin\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            CollectionsChartWidget::class,
            EnrollmentsChartWidget::class,
            LatestPaymentsTableWidget::class,
            LatestEnrollmentsTableWidget::class,
            OverdueInstallmentsTableWidget::class,
            LatestCoursesTableWidget::class,
            LatestStudentsTableWidget::class,
            LatestTeachersTableWidget::class,
        ];
    }
}

