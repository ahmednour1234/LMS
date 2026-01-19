<?php

namespace App\Policies;

use App\Domain\Training\Models\ExamAttempt;
use App\Domain\Training\Models\Teacher;

class StudentExamAttemptPolicy
{
    public function viewAny(Teacher $teacher): bool
    {
        return true;
    }

    public function view(Teacher $teacher, ExamAttempt $attempt): bool
    {
        return $attempt->exam->course->owner_teacher_id === $teacher->id;
    }

    public function grade(Teacher $teacher, ExamAttempt $attempt): bool
    {
        return $attempt->exam->course->owner_teacher_id === $teacher->id;
    }
}