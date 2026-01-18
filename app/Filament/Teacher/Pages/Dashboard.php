<?php

namespace App\Filament\Teacher\Pages;

use App\Filament\Teacher\Widgets\LatestCoursesTableWidget;
use App\Filament\Teacher\Widgets\LatestTaskSubmissionsTableWidget;
use App\Filament\Teacher\Widgets\TeacherAnalyticsOverviewWidget;
use App\Filament\Teacher\Widgets\TeacherStatsOverviewWidget;
use App\Filament\Teacher\Widgets\TopCoursesByEnrollmentsWidget;
use App\Filament\Teacher\Widgets\WorstCoursesWidget;
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
            TeacherAnalyticsOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            LatestCoursesTableWidget::class,
            TopCoursesByEnrollmentsWidget::class,
            WorstCoursesWidget::class,
            LatestTaskSubmissionsTableWidget::class,
        ];
    }
}
