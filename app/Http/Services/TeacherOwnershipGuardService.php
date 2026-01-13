<?php

namespace App\Http\Services;

use App\Domain\Training\Models\Course;
use App\Domain\Training\Models\CourseSection;
use App\Domain\Training\Models\Lesson;
use App\Domain\Training\Models\LessonItem;
use App\Domain\Training\Models\Exam;
use App\Domain\Training\Models\ExamQuestion;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TeacherOwnershipGuardService
{
    public function assertCourseOwner(int $teacherId, Course $course): void
    {
        if ((int) $course->owner_teacher_id !== (int) $teacherId) {
            throw new HttpException(403, 'You do not own this course.');
        }
    }

    public function assertSectionOwner(int $teacherId, CourseSection $section): void
    {
        $course = $section->course()->first();
        if (!$course) throw new HttpException(404, 'Course not found.');
        $this->assertCourseOwner($teacherId, $course);
    }

    public function assertLessonOwner(int $teacherId, Lesson $lesson): void
    {
        $section = $lesson->section()->first();
        if (!$section) throw new HttpException(404, 'Section not found.');
        $this->assertSectionOwner($teacherId, $section);
    }

    public function assertLessonItemOwner(int $teacherId, LessonItem $item): void
    {
        $lesson = $item->lesson()->first();
        if (!$lesson) throw new HttpException(404, 'Lesson not found.');
        $this->assertLessonOwner($teacherId, $lesson);
    }

    public function assertExamOwner(int $teacherId, Exam $exam): void
    {
        $course = $exam->course()->first();
        if (!$course) throw new HttpException(404, 'Course not found.');
        $this->assertCourseOwner($teacherId, $course);
    }

    public function assertExamQuestionOwner(int $teacherId, ExamQuestion $question): void
    {
        $exam = $question->exam()->first();
        if (!$exam) throw new HttpException(404, 'Exam not found.');
        $this->assertExamOwner($teacherId, $exam);
    }
}
