<?php

namespace App\Filament\Admin\Resources\CourseResource\Pages;

use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use App\Filament\Admin\Resources\CourseResource;
use App\Filament\Admin\Resources\CourseResource\Pages\Actions\CreateLessonAction;
use App\Filament\Admin\Resources\CourseResource\Pages\Actions\CreateSectionAction;
use App\Filament\Admin\Resources\CourseResource\Pages\Actions\EditLessonAction;
use App\Filament\Admin\Resources\CourseResource\Pages\Actions\EditSectionAction;
use App\Domain\Training\Models\Course;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ManageCourseStudio extends Page
{
    protected static string $resource = CourseResource::class;

    protected static string $view = 'filament.admin.resources.course-resource.pages.manage-course-studio';

    public ?int $selectedSectionId = null;

    public ?int $selectedLessonId = null;

    public Course $record;

    public function mount(int | string $record): void
    {
        // Check authorization first
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->hasRole('admin')) {
            abort(403);
        }

        // Resolve the record
        $this->record = Course::findOrFail($record);
    }

    public function getRecord(): Course
    {
        return $this->record;
    }

    public function getSections()
    {
        return $this->getRecord()
            ->sections()
            ->with(['lessons' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('order')
            ->get();
    }

    public function getSelectedLesson(): ?Lesson
    {
        if (!$this->selectedLessonId) {
            return null;
        }

        return Lesson::with('section')->find($this->selectedLessonId);
    }

    public function selectLesson(int $lessonId): void
    {
        $this->selectedLessonId = $lessonId;
        $lesson = Lesson::find($lessonId);
        if ($lesson) {
            $this->selectedSectionId = $lesson->section_id;
        }
    }

    public function selectSection(int $sectionId): void
    {
        $this->selectedSectionId = $sectionId;
        $this->selectedLessonId = null;
    }

    public function reorderSections(array $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order as $index => $sectionId) {
                CourseSection::where('id', $sectionId)
                    ->where('course_id', $this->getRecord()->id)
                    ->update(['order' => $index + 1]);
            }
        });

        Notification::make()
            ->title(__('Sections reordered successfully'))
            ->success()
            ->send();

        $this->dispatch('sections-reordered');
    }

    public function reorderLessons(int $sectionId, array $order): void
    {
        DB::transaction(function () use ($sectionId, $order) {
            foreach ($order as $index => $lessonId) {
                Lesson::where('id', $lessonId)
                    ->where('section_id', $sectionId)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        Notification::make()
            ->title(__('Lessons reordered successfully'))
            ->success()
            ->send();

        $this->dispatch('lessons-reordered');
    }

    public function toggleSectionActive(int $sectionId): void
    {
        $section = CourseSection::findOrFail($sectionId);
        $section->update(['is_active' => !$section->is_active]);

        Notification::make()
            ->title($section->is_active ? __('Section activated') : __('Section deactivated'))
            ->success()
            ->send();

        $this->dispatch('section-toggled');
    }

    public function toggleLessonActive(int $lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        $lesson->update(['is_active' => !$lesson->is_active]);

        Notification::make()
            ->title($lesson->is_active ? __('Lesson activated') : __('Lesson deactivated'))
            ->success()
            ->send();

        $this->dispatch('lesson-toggled');
    }

    public function moveSectionUp(int $sectionId): void
    {
        $section = CourseSection::findOrFail($sectionId);
        $previousSection = CourseSection::where('course_id', $section->course_id)
            ->where('order', '<', $section->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($previousSection) {
            $tempOrder = $section->order;
            $section->update(['order' => $previousSection->order]);
            $previousSection->update(['order' => $tempOrder]);
            $this->dispatch('$refresh');
        }
    }

    public function moveSectionDown(int $sectionId): void
    {
        $section = CourseSection::findOrFail($sectionId);
        $nextSection = CourseSection::where('course_id', $section->course_id)
            ->where('order', '>', $section->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextSection) {
            $tempOrder = $section->order;
            $section->update(['order' => $nextSection->order]);
            $nextSection->update(['order' => $tempOrder]);
            $this->dispatch('$refresh');
        }
    }

    public function moveLessonUp(int $lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        $previousLesson = Lesson::where('section_id', $lesson->section_id)
            ->where('sort_order', '<', $lesson->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previousLesson) {
            $tempOrder = $lesson->sort_order;
            $lesson->update(['sort_order' => $previousLesson->sort_order]);
            $previousLesson->update(['sort_order' => $tempOrder]);
            $this->dispatch('$refresh');
        }
    }

    public function moveLessonDown(int $lessonId): void
    {
        $lesson = Lesson::findOrFail($lessonId);
        $nextLesson = Lesson::where('section_id', $lesson->section_id)
            ->where('sort_order', '>', $lesson->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($nextLesson) {
            $tempOrder = $lesson->sort_order;
            $lesson->update(['sort_order' => $nextLesson->sort_order]);
            $nextLesson->update(['sort_order' => $tempOrder]);
            $this->dispatch('$refresh');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label(__('Back'))
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => CourseResource::getUrl('view', ['record' => $this->getRecord()])),
        ];
    }

    protected function getActions(): array
    {
        return [
            CreateSectionAction::make(),
            CreateLessonAction::make(),
            EditSectionAction::make(),
            EditLessonAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('Course Studio') . ': ' . ($this->getRecord()->name[app()->getLocale()] ?? $this->getRecord()->name['en'] ?? 'Untitled');
    }
}

