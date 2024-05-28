<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class StudentController extends Controller
{
    public function viewSchedules()
{
    try {
        // Get the authenticated student
        $student = auth()->user();

        // Retrieve approved schedules for the courses in the student's department
        $schedules = Schedule::whereHas('course', function ($query) use ($student) {
            $query->where('department_id', $student->department_id);
        })
        ->where('approved', true) // Filter only approved schedules
        ->with('course') // Eager load the associated course
        ->orderBy('schedule_date', 'asc')
        ->orderBy('start_time', 'asc')
        ->orderBy('end_time', 'asc')
        ->get();

        // Transform the schedule data to the desired format
        $formattedSchedules = $schedules->map(function ($schedule) {
            return [
                'start' => $schedule->schedule_date . ' ' . $schedule->start_time,
                'end' => $schedule->schedule_date . ' ' . $schedule->end_time,
                'title' => $schedule->course->name,
            ];
        });

        // Return success response with the formatted schedules
        return response()->json([
            'success' => true,
            'data' => $formattedSchedules,
        ], 200);
    } catch (\Exception $e) {
        // Return error response if retrieval fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve schedules',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
