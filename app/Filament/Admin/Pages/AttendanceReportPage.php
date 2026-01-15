<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\Teacher;
use App\Http\Services\Reports\AttendanceReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class AttendanceReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string $view = 'filament.admin.pages.attendance-report-page';

    protected static ?string $navigationGroup = 'reports';

    protected static ?int $navigationSort = 21;

    public static function getNavigationLabel(): string
    {
        return __('attendance.attendance_report');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Reports');
    }

    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $teacherId = null;
    public ?int $courseId = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->form->fill([
            'dateFrom' => now()->startOfMonth(),
            'dateTo' => now(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('dateFrom')
                    ->label(__('filters.date_from'))
                    ->required()
                    ->default(now()->startOfMonth()),
                DatePicker::make('dateTo')
                    ->label(__('filters.date_to'))
                    ->required()
                    ->default(now()),
                Select::make('teacherId')
                    ->label(__('Teacher'))
                    ->options(Teacher::pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                Select::make('courseId')
                    ->label(__('Course'))
                    ->options(Course::query()->get()->mapWithKeys(function ($course) {
                        $name = is_array($course->name) ? ($course->name['en'] ?? $course->name['ar'] ?? '') : $course->name;
                        return [$course->id => $name];
                    }))
                    ->searchable()
                    ->nullable(),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $this->form->validate();
        $data = $this->form->getState();
        $this->dateFrom = $data['dateFrom'] ?? $this->dateFrom;
        $this->dateTo = $data['dateTo'] ?? $this->dateTo;
        $this->teacherId = $data['teacherId'] ?? $this->teacherId;
        $this->courseId = $data['courseId'] ?? $this->courseId;
    }
}
