<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Teacher\Widgets\LatestCoursesTableWidget;
use App\Filament\Teacher\Widgets\LatestTaskSubmissionsTableWidget;
use App\Filament\Teacher\Widgets\TeacherStatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    public function getWidgets(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TeacherStatsOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            LatestCoursesTableWidget::class,
            LatestTaskSubmissionsTableWidget::class,
        ];
    }
}
