<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Jobs\SendScheduleApprovalNotifications;
use App\Models\Admin;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Course;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\Schedule;

use Illuminate\Database\Eloquent\ModelNotFoundException;

// ERROR FACULTY FIX
class AdminController extends Controller
{
    /**
     * Create a new instance of AdminController.
     */


     /**
 * Update the specified admin's details.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $id
 * @return \Illuminate\Http\Response
 */
public function adminUpdate(Request $request, $id)
{
    // Find the admin by ID
    $admin = Admin::findOrFail($id);

    // Validate the request data
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:admins,email,' . $admin->id,
        'password' => 'sometimes|string|min:8|confirmed',
    ])
    ;

    // If validation fails, return error response
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    try {
        // Update the admin data
        $admin->update([
            'name' => $request->input('name', $admin->name),
            'email' => $request->input('email', $admin->email),
            'password' => $request->filled('password') ? bcrypt($request->input('password')) : $admin->password,
        ]);

        // Make the password hidden from the response
        $admin->makeHidden('password');

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Admin updated successfully',
            'data' => $admin,
        ], 200);
    } catch (\Exception $e) {
        // Return error response if admin update fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to update admin',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Create a new faculty.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createFaculty(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:faculties',
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
            // Create a new faculty
            $faculty = Faculty::create([
                'name' => $request->input('name'),
            ]);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Faculty created successfully',
                'data' => $faculty,
            ], 201);
        } catch (\Exception $e) {
            // Return error response if faculty creation fails
            return response()->json([
                'success' => false,
                'message' => 'Failed to create faculty',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createDepartment(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments',
            'faculty_id' => 'required|exists:faculties,id',
            'max_courses_per_lecturer' => 'integer|max:5|min:1',
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
            // Get the max_courses_per_lecturer from the request or use default value of 5
            $maxCoursesPerLecturer = $request->input('max_courses_per_lecturer', 5);

            // Create a new department
            $department = Department::create([
                'name' => $request->input('name'),
                'faculty_id' => $request->input('faculty_id'),
                'max_courses_per_lecturer' => $maxCoursesPerLecturer,
            ]);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Department created successfully',
                'data' => $department,
            ], 200);
        } catch (\Exception $e) {
            // Return error response if department creation fails
            return response()->json([
                'success' => false,
                'message' => 'Failed to create department',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

public function createCourse(Request $request)
{
    // Validate the request data
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255|unique:courses',
        'department_id' => 'required|exists:departments,id',
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
        // Create a new course
        $course = Course::create([
            'name' => $request->input('name'),
            'department_id' => $request->input('department_id'),
        ]);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Course created successfully',
            'data' => $course,
        ], 201);
    } catch (\Exception $e) {
        // Return error response if course creation fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to create course',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function assignCourseToLecturer(Request $request)
{
    // Validate the request data
    $validator = Validator::make($request->all(), [
        'lecturer_id' => 'required|exists:lecturers,id',
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
        // Find the lecturer and course by their IDs
        $lecturer = Lecturer::findOrFail($request->input('lecturer_id'));
        $course = Course::findOrFail($request->input('course_id'));

        // Check if the course department matches the lecturer's department
        if ($course->department_id !== $lecturer->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'The course and lecturer must belong to the same department.',
            ], 403);
        }

        // Check if the course already has a lecturer assigned
        if ($course->lecturer_id) {
            return response()->json([
                'success' => false,
                'message' => 'This course already has a lecturer assigned.',
            ], 403);
        }

        // Check if the lecturer is already assigned to the maximum number of courses in their department
        $maxCoursesPerLecturer = config('app.max_courses_per_lecturer', 5);
        $lecturerCourseCount = $course->department->courses()->where('lecturer_id', $lecturer->id)->count();
        if ($lecturerCourseCount >= $maxCoursesPerLecturer) {
            return response()->json([
                'success' => false,
                'message' => 'The lecturer cannot be assigned to more courses in this department.',
            ], 403);
        }

        // Assign the lecturer to the course
        $course->lecturer_id = $lecturer->id;
        $course->save();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Course assigned to the lecturer successfully',
        ], 200);
    } catch (\Exception $e) {
        // Return error response if course assignment fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to assign course to the lecturer',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function addLecturer(Request $request)
{
    // Validate the request data
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:lecturers',
        'phone' => 'required|string|min:9|max:15',
        'password' => 'required|string|min:8', // Add password validation
        'department_id' => 'required|exists:departments,id',
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
        // Get the department by ID
        $department = Department::findOrFail($request->input('department_id'));

        // Create a new lecturer
        $lecturer = Lecturer::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => bcrypt($request->input('password')), // Hash the password
            'faculty_id' => $department->faculty_id, // Get the faculty_id from the department
            'department_id' => $department->id,
        ]);

        // Make the password hidden from the response
        $lecturer->makeHidden('password');

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Lecturer added successfully',
            'data' => $lecturer,
        ], 201);
    } catch (ModelNotFoundException $e) {
        // Return error response if department is not found
        return response()->json([
            'success' => false,
            'message' => 'Department not found',
        ], 404);
    } catch (\Exception $e) {
        // Return error response if lecturer creation fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to add lecturer',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function viewAllLecturers()
{
    try {
        $lecturers = Lecturer::with('faculty', 'department')
            ->get()
            ->map(function ($lecturer) {
                return [
                    'id' => $lecturer->id,
                    'fullname' => $lecturer->name,
                    'email' => $lecturer->email,
                    'faculty' => $lecturer->faculty->name,
                    'department' => $lecturer->department->name,
                    'department_id' => $lecturer->department->id
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $lecturers,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve lecturers',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function viewAllStudents()
{
    try {
        $students = Student::with('department.faculty')
        ->get()
        ->map(function ($student) {
            return [
                'id' => $student->id,
                'school_id' => $student->student_id,
                'fullname' => $student->name,
                'faculty' => $student->department->faculty->name,
                'department' => $student->department->name,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $students,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve students',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function viewAllCourses()
{
    try {
        $courses = Course::with('lecturer:id,name', 'department:id,name')->get([
            'id',
            'name',
            'lecturer_id',
            'department_id',
        ]);

        $formattedCourses = $courses->map(function ($course) {
            return [
                'id' => $course->id,
                'name' => $course->name,
                'lecturer' => $course->lecturer ? $course->lecturer->name : null,
                'department_id' => $course->department->id,
                'department' => $course->department->name
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedCourses,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve courses',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function viewAllDepartments()
{
    try {
        $departments = Department::with('courses:id,name,department_id')->get(['id', 'name']);
        $data = $departments->map(function ($department) {
            return [
                'id' => $department->id,
                'name' => $department->name,
                'courses' => $department->courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'name' => $course->name,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve departments',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function viewAllFaculties()
{
    try {
        $faculties = Faculty::with('departments:id,name,faculty_id')->get(['id', 'name']);

        $data = $faculties->map(function ($faculty) {
            return [
                'id' => $faculty->id,
                'name' => $faculty->name,
                'departments' => $faculty->departments->map(function ($department) {
                    return [
                        'id' => $department->id,
                        'name' => $department->name,
                    ];
                }),
            ];
        });

        return response()->json([
            'data' => $data,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve faculties',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function updateLecturer(Request $request, $id)
{
    // Find the lecturer by ID
    $lecturer = Lecturer::findOrFail($id);

    // Validate the request data
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:lecturers,email,' . $lecturer->id,
        'password' => 'sometimes|string|min:8|confirmed',
        'faculty_id' => 'sometimes|exists:faculties,id',
        'department_id' => 'sometimes|exists:departments,id',
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
            'faculty_id' => $request->input('faculty_id', $lecturer->faculty_id),
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
            'message' => 'Failed to update lecturer',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function updateFaculty(Request $request, $id)
{
    // Find the faculty by ID
    $faculty = Faculty::findOrFail($id);

    // Validate the request data
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255|unique:faculties,name,' . $faculty->id,
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
        // Update the faculty data
        $faculty->update([
            'name' => $request->filled('name') ? $request->input('name') : $faculty->name,
        ]);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Faculty updated successfully',
            'data' => $faculty,
        ], 200);
    }
    catch (ModelNotFoundException $e) {
            // Return error response if faculty not found
            return response()->json([
                'success' => false,
                'message' => 'Faculty not found',
            ], 404);
    } catch (\Exception $e) {
        // Return error response if faculty update fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to update faculty',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function updateDepartment(Request $request, $id)
{
    // Find the department by ID
    $department = Department::findOrFail($id);

    // Validate the request data
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255|unique:departments,name,' . $department->id,
        'faculty_id' => 'sometimes|exists:faculties,id',
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
        // Update the department data
        $department->update([
            'name' => $request->input('name', $department->name),
            'faculty_id' => $request->input('faculty_id', $department->faculty_id),
        ]);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully',
            'data' => $department,
        ], 200);
    } catch (\Exception $e) {
        // Return error response if department update fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to update department',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function updateCourse(Request $request, $id)
{
    // Find the course by ID
    $course = Course::findOrFail($id);

    // Validate the request data
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255|unique:courses,name,' . $course->id,
        'department_id' => 'sometimes|exists:departments,id',
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
        // Update the course data
        $course->update([
            'name' => $request->input('name', $course->name),
            'department_id' => $request->input('department_id', $course->department_id),
        ]);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully',
            'data' => $course,
        ], 200);
    } catch (\Exception $e) {
        // Return error response if course update fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to update course',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function deleteLecturer($id)
{
    try {
        // Find the lecturer by ID
        $lecturer = Lecturer::findOrFail($id);

        // Delete the lecturer
        $lecturer->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Lecturer deleted successfully',
        ], 200);
    }catch (ModelNotFoundException $e) {
        // Return error response if faculty not found
        return response()->json([
            'success' => false,
            'message' => 'Lecturer not found',
        ], 404);
    }
    catch (\Exception $e) {
        // Return error response if lecturer deletion fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete lecturer',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function deleteFaculty($id)
{
    try {
        // Find the faculty by ID
        $faculty = Faculty::findOrFail($id);

        // Delete the faculty
        $faculty->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Faculty deleted successfully',
        ], 200);
    }catch (ModelNotFoundException $e) {
        // Return error response if faculty not found
        return response()->json([
            'success' => false,
            'message' => 'Faculty not found',
        ], 404);
    }
    catch (\Exception $e) {
        // Return error response if faculty deletion fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete faculty',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function deleteDepartment($id)
{
    try {
        // Find the department by ID
        $department = Department::findOrFail($id);

        // Delete the department
        $department->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully',
        ], 200);
    }catch (ModelNotFoundException $e) {
        // Return error response if faculty not found
        return response()->json([
            'success' => false,
            'message' => 'Department not found',
        ], 404);
    }
    catch (\Exception $e) {
        // Return error response if department deletion fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete department',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function deleteCourse($id)
{
    try {
        // Find the course by ID
        $course = Course::findOrFail($id);

        // Delete the course
        $course->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully',
        ], 200);
    }
    catch (ModelNotFoundException $e) {
        // Return error response if faculty not found
        return response()->json([
            'success' => false,
            'message' => 'Course not found',
        ], 404);
    }
    catch (\Exception $e) {
        // Return error response if course deletion fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete course',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function approveSchedule(Request $request, $id)
{
    try {
        // Find the schedule by ID
        $schedule = Schedule::findOrFail($id);

        // Check if the schedule is already approved
        if ($schedule->approved) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule is already approved',
            ], 400);
        }

        // Update the schedule's approval status
        $schedule->update([
            'approved' => true,
        ]);

        // Dispatch a job to send notifications
        SendScheduleApprovalNotifications::dispatch($schedule);

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Schedule approved successfully',
            'data' => $schedule,
        ], 200);
    } catch (ModelNotFoundException $e) {
        // Return error response if schedule is not found
        return response()->json([
            'success' => false,
            'message' => 'Schedule not found',
        ], 404);
    } catch (\Exception $e) {
        // Return error response if schedule approval fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to approve schedule',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getAllSchedules()
{
    try {
        // Retrieve all schedules with associated course and lecturer details
        $schedules = Schedule::with('course', 'lecturer')->get();

        // Transform the schedules data to the desired format
        $formattedSchedules = $schedules->map(function ($schedule, $index) {
            return [
                'id' => $schedule->id,
                'course' => $schedule->course->name,
                'lecturer' => $schedule->lecturer->name,
                'date' => $schedule->schedule_date,
                'startTime' => $this->formatMyTime($schedule->start_time),
                'endTime' => $this->formatMyTime($schedule->end_time),
                'approved' => $schedule->approved,
            ];
        });

        // Return success response with the formatted schedules data
        return response()->json([
            'success' => true,
            'message' => 'Schedules retrieved successfully',
            'data' => $formattedSchedules,
        ], 200);
    } catch (\Exception $e) {
        // Return error response if schedule retrieval fails
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve schedules',
            'error' => $e->getMessage(),
        ], 500);
    }
}

private function formatMyTime($timeString)
{
    return date('h:i A', strtotime($timeString));
}

/**
 * Get the total count of faculties, departments, lecturers, and students.
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function getTotalCounts()
{
    try {
        $totalFaculties = Faculty::count();
        $totalDepartments = Department::count();
        $totalLecturers = Lecturer::count();
        $totalStudents = Student::count();

        $data = [
            [
                'id' => 1,
                'title' => 'Faculties',
                'value' => $totalFaculties,
            ],
            [
                'id' => 2,
                'title' => 'Departments',
                'value' => $totalDepartments,
            ],
            [
                'id' => 3,
                'title' => 'Lecturers',
                'value' => $totalLecturers,
            ],
            [
                'id' => 4,
                'title' => 'Students',
                'value' => $totalStudents,
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Total counts retrieved successfully',
            'data' => $data,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve total counts',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
