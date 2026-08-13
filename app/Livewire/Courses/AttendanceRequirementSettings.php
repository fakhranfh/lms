<?php

namespace App\Livewire\Courses;

use App\Enums\AttendanceRequirementType;
use App\Models\Course;
use App\Services\AttendanceRequirementService;
use App\Services\CourseAttendanceSettingService;
use App\Support\CourseTabs;
use App\Support\CurrentSchool;
use Livewire\Component;

class AttendanceRequirementSettings extends Component
{
    public Course $course;

    public string $requirementType = 'manual_checkin';

    public string $label = '';

    public string $minimalAttendance = '0';

    public ?string $errorMessage = null;

    public function mount(CurrentSchool $currentSchool, Course $course, CourseAttendanceSettingService $courseAttendanceSettingService): void
    {
        $schoolId = $currentSchool->getSchoolId() ?? auth()->user()->school_id;
        abort_unless(auth()->user()->can('attendance.manage') && $course->school_id === $schoolId, 403);

        $this->course = $course;

        $setting = $courseAttendanceSettingService->findByCourse($course->id);
        $this->minimalAttendance = (string) ($setting !== null ? $setting->minimal_attendance : 0);
    }

    public function addRequirement(AttendanceRequirementService $attendanceRequirementService): void
    {
        $this->validate([
            'requirementType' => 'required|string|in:manual_checkin,forum_completed,class_duration_completed',
            'label' => 'required|string|max:255',
        ]);

        $order = $attendanceRequirementService->forCourse($this->course->id)->count() + 1;

        $attendanceRequirementService->create([
            'course_id' => $this->course->id,
            'requirement_type' => AttendanceRequirementType::from($this->requirementType),
            'label' => $this->label,
            'order' => $order,
        ]);

        $this->reset(['label']);
        $this->requirementType = 'manual_checkin';
    }

    public function deleteRequirement(string $requirementId, AttendanceRequirementService $attendanceRequirementService): void
    {
        $requirement = $attendanceRequirementService->find($requirementId);

        if (! $requirement || $requirement->course_id !== $this->course->id) {
            $this->errorMessage = __('Requirement not found.');

            return;
        }

        $attendanceRequirementService->delete($requirementId);
    }

    public function moveRequirement(string $requirementId, string $direction, AttendanceRequirementService $attendanceRequirementService): void
    {
        $requirements = $attendanceRequirementService->forCourse($this->course->id);
        $index = $requirements->search(fn ($r) => $r->id === $requirementId);

        if ($index === false) {
            return;
        }

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapIndex < 0 || $swapIndex >= $requirements->count()) {
            return;
        }

        $current = $requirements[$index];
        $swap = $requirements[$swapIndex];

        $attendanceRequirementService->update($current->id, ['order' => $swap->order]);
        $attendanceRequirementService->update($swap->id, ['order' => $current->order]);
    }

    public function saveMinimalAttendance(CourseAttendanceSettingService $courseAttendanceSettingService): void
    {
        $this->validate([
            'minimalAttendance' => 'required|integer|min:0',
        ]);

        $setting = $courseAttendanceSettingService->findByCourse($this->course->id);

        if ($setting) {
            $courseAttendanceSettingService->update($setting->id, ['minimal_attendance' => (int) $this->minimalAttendance]);
        } else {
            $courseAttendanceSettingService->create([
                'course_id' => $this->course->id,
                'minimal_attendance' => (int) $this->minimalAttendance,
            ]);
        }
    }

    public function render(AttendanceRequirementService $attendanceRequirementService)
    {
        return view('livewire.courses.attendance-requirement-settings', [
            'course' => $this->course,
            'requirements' => $attendanceRequirementService->forCourse($this->course->id),
            'requirementTypes' => AttendanceRequirementType::cases(),
            'courseTabs' => CourseTabs::build($this->course, 'attendance'),
        ])
            ->extends('layouts.app', ['topbarTitle' => $this->course->title])
            ->section('app-content');
    }
}
