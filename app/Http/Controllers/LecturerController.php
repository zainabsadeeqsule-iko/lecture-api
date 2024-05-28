<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Lecturer;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\Student;

class LecturerController extends Controller
{
         /**
 * Update the specified lecturer's details.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $id
 * @return \Illuminate\Http\Response
 */
public function lecturerUpdate(Request $request, $id)
{
    // Find the lecturer by ID
    $lecturer = Lecturer::findOrFail($id);

    // Validate the request data
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:lecturers,email,' . $lecturer->id,
        'password' => 'sometimes|string|min:8|confirmed',
    ]);

    // If validation fails, return error response
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    try {
        // Update the lecturer data
        $lecturer->update([
            'name' => $request->input('name', $lecturer->name),
            'email' => $request->input('email', $lecturer->email),
            'password' => $request->filled('password') ? bcrypt($request->input('password')) : $lecturer->password,
        ]);

        // Make the password hidden from the response
        $lecturer->makeHidden('password');

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Lecturer updated successfully',
            'data' => $lecturer,
        ], 200);
    } catch (\Exception $e) {
        // Return error response if lecturer update fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to update admin',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function createSchedule(Request $request)
{
    // Validate the request data
    $validator = Validator::make($request->all(), [
        'start_time' => 'required|date_format:H:i:s',
        'end_time' => 'required|date_format:H:i:s|after:start_time',
        'schedule_date' => 'required|date_format:Y-m-d|after:today',
        'course_id' => 'required|exists:courses,id',
    ]);

    // If validation fails, return error response
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    try {
        // Get the authenticated lecturer
        $lecturer = auth()->user();

        // Check if the course is assigned to the lecturer
        $course = Course::find($request->input('course_id'));
        if ($course->lecturer_id !== $lecturer->id) {
            return response()->json([
                'success' => false,
                'message' => 'The course is not assigned to the lecturer.',
            ], 403);
        }

        // Check if there is a conflicting schedule
        $conflictingSchedule = Schedule::where('lecturer_id', $lecturer->id)
            ->where('schedule_date', $request->input('schedule_date'))
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_time', [$request->input('start_time'), $request->input('end_time')])
                    ->orWhereBetween('end_time', [$request->input('start_time'), $request->input('end_time')])
                    ->orWhere(function ($query) use ($request) {
                        $query->where('start_time', '<=', $request->input('start_time'))
                            ->where('end_time', '>=', $request->input('end_time'));
                    });
            })
            ->exists();

        if ($conflictingSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'The schedule conflicts with an existing schedule.',
            ], 409);
        }

        // Create a new schedule
        $schedule = $course->schedules()->create([
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'schedule_date' => $request->input('schedule_date'),
            'course_id' => $request->input('course_id'),
            'lecturer_id' => $lecturer->id,
        ]);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule,
        ], 201);
    } catch (\Exception $e) {
        // Return error response if schedule creation fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to create schedule',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function deleteSchedule(Request $request, $scheduleId)
{
    try {
        // Get the authenticated lecturer
        $lecturer = auth()->user();

        // Find the schedule by ID
        $schedule = Schedule::findOrFail($scheduleId);

        // Check if the schedule belongs to the authenticated lecturer
        if ($schedule->lecturer_id !== $lecturer->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this schedule.',
            ], 403);
        }

        // Delete the schedule
        $schedule->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully',
        ], 200);
    } catch (ModelNotFoundException $e) {
        // Return error response if schedule is not found
        return response()->json([
            'success' => false,
            'message' => 'Schedule not found',
        ], 404);
    } catch (\Exception $e) {
        // Return error response if schedule deletion fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete schedule',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function updateSchedule(Request $request, $scheduleId)
{
    // Validate the request data
    $validator = Validator::make($request->all(), [
        'start_time' => 'required|date_format:H:i:s',
        'end_time' => 'required|date_format:H:i:s|after:start_time',
        'schedule_date' => 'required|date_format:Y-m-d|after:today',
    ]);

    // If validation fails, return error response
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    try {
        // Get the authenticated lecturer
        $lecturer = auth()->user();

        // Find the schedule by ID
        $schedule = Schedule::findOrFail($scheduleId);

        // Check if the schedule belongs to the authenticated lecturer
        if ($schedule->lecturer_id !== $lecturer->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this schedule.',
            ], 403);
        }

        // Check if there is a conflicting schedule
        $conflictingSchedule = Schedule::where('lecturer_id', $lecturer->id)
            ->where('schedule_date', $request->input('schedule_date'))
            ->where('id', '!=', $schedule->id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_time', [$request->input('start_time'), $request->input('end_time')])
                    ->orWhereBetween('end_time', [$request->input('start_time'), $request->input('end_time')])
                    ->orWhere(function ($query) use ($request) {
                        $query->where('start_time', '<=', $request->input('start_time'))
                            ->where('end_time', '>=', $request->input('end_time'));
                    });
            })
            ->exists();

        if ($conflictingSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'The updated schedule conflicts with an existing schedule.',
            ], 409);
        }

        // Update the schedule
        $schedule->start_time = $request->input('start_time');
        $schedule->end_time = $request->input('end_time');
        $schedule->schedule_date = $request->input('schedule_date');
        $schedule->save();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule,
        ], 200);
    } catch (ModelNotFoundException $e) {
        // Return error response if schedule is not found
        return response()->json([
            'success' => false,
            'message' => 'Schedule not found',
        ], 404);
    } catch (\Exception $e) {
        // Return error response if schedule update fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to update schedule',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function viewSchedules()
{
    try {
        // Get the authenticated lecturer
        $lecturer = auth()->user();

        // Retrieve all schedules for the authenticated lecturer
        $schedules = Schedule::where('lecturer_id', $lecturer->id)
            ->with('course') // Eager load the associated course
            ->orderBy('schedule_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // Return success response with the schedules
        return response()->json([
            'success' => true,
            'data' => $schedules,
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

public function viewCourses()
{
    try {
        // Get the authenticated lecturer
        $lecturer = auth()->user();

        // Retrieve all courses assigned to the lecturer in their department, including student count
        $courses = Course::where('lecturer_id', $lecturer->id)
            ->with('department.students') // Eager load the associated department and its students
            ->get();

        // Transform the courses data to include department name and student count
        $transformedCourses = $courses->map(function ($course) {
            return [
                'id' => $course->id,
                'name' => $course->name,
                'department' => $course->department->name,
                'student_count' => $course->department->students->count(),
            ];
        });

        // Return success response with the transformed courses data
        return response()->json([
            'success' => true,
            'data' => $transformedCourses,
        ], 200);
    } catch (\Exception $e) {
        // Return error response if retrieval fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve courses',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
