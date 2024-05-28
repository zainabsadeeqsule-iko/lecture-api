

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SystemController;

Route::group([

    'middleware' => 'api'

], function () {

    // Admin Routes
    Route::post('/admin-login', [AuthController::class, 'adminLogin'] );

    // Lecturer Routes
    Route::post('/lecturer-login', [AuthController::class, 'lecturerLogin'] );


    // Student Routes
    Route::post('/student-login', [AuthController::class, 'studentLogin'] );
    Route::post('/student-register', [AuthController::class, 'studentRegister'] );


    // System Routes
    Route::get('/fetch-departments', [SystemController::class, 'fetchDepartments'] );





});

Route::group([

    'middleware' => ['api','auth:admin'],

], function () {
    // Admin Routes
    Route::post('/admin-logout', [AuthController::class, 'adminlogout'] );

    Route::get('/admin-dashboard', [AdminController::class, 'adminDashboard'] );
    Route::patch('/admin-update/{id}', [AdminController::class, 'adminUpdate'] );

    Route::get('/admin-view-schedules', [AdminController::class, 'getAllSchedules'] );
    Route::patch('/admin-approve-schedule/{id}', [AdminController::class, 'approveSchedule'] );  // Notify All Students linked to this schedule


    Route::post('/admin-add-lecturer', [AdminController::class, 'addLecturer'] );
    Route::get('/admin-view-lecturers', [AdminController::class, 'viewAllLecturers']);
    Route::patch('/admin-update-lecturer/{id}', [AdminController::class, 'updateLecturer'] );
    Route::delete('/admin-delete-lecturer/{id}', [AdminController::class, 'deleteLecturer'] );

    Route::get('/admin-view-students', [AdminController::class, 'viewAllStudents'] );

    Route::post('/admin-create-faculty', [AdminController::class, 'createFaculty']);
    Route::get('/admin-view-faculty', [AdminController::class, 'viewAllFaculties'] );
    Route::patch('/admin-update-faculty/{id}', [AdminController::class, 'updateFaculty'] );
    Route::delete('/admin-delete-faculty/{id}', [AdminController::class, 'deleteFaculty'] );


    Route::post('/admin-create-department', [AdminController::class, 'createDepartment']);
    Route::get('/faculties/departments', [AdminController::class, 'viewAllDepartments']);
    Route::patch('/admin-update-department/{id}', [AdminController::class, 'updateDepartment'] );
    Route::delete('/admin-delete-department/{id}', [AdminController::class, 'deleteDepartment'] );

    Route::post('/admin-create-course', [AdminController::class, 'createCourse'] );
    Route::patch('/admin/assign-course-to-lecturer', [AdminController::class, 'assignCourseToLecturer']);
    Route::patch('/admin-update-course/{id}', [AdminController::class, 'updateCourse'] );
    Route::get('/departments/courses', [AdminController::class, 'viewAllCourses']);
    Route::delete('/admin-delete-course/{id}', [AdminController::class, 'deleteCourse'] );

    Route::get('/getTotalCounts', [AdminController::class, 'getTotalCounts'] );


});

Route::group([

    'middleware' => ['api','auth:lecturer'],

], function () {
    // Lecturer Routes

    // lecturer should be able to view their courses they are teaching
    // Route::get('/lecturer-courses', [LecturerController::class, 'viewCoursesTeaching']);
    Route::post('/lecturer-logout', [AuthController::class, 'lecturerlogout'] );

    Route::get('/lecturer-dashboard', [LecturerController::class, 'lecturerDashboard'] );

    Route::patch('/lecturer-update/{id}', [LecturerController::class, 'lecturerUpdate'] );

    Route::get('/lecturer-view-courses', [LecturerController::class, 'viewCourses'] );

    Route::post('/lecturer-create-schedule', [LecturerController::class, 'createSchedule'] );
    Route::delete('/lecturer-delete-schedule/{id}', [LecturerController::class, 'deleteSchedule'] );
    Route::patch('/lecturer-update-schedule/{id}', [LecturerController::class, 'updateSchedule'] ); // Notify All Students of Change in Schedule
    Route::get('/lecturer-view-schedules', [LecturerController::class, 'viewSchedules'] );

});

Route::group([

    'middleware' => ['api','auth:student'],

], function () {
     // Student Routes
     Route::post('/student-logout', [AuthController::class, 'studentlogout'] );

     Route::get('/student-dashboard', [StudentController::class, 'studentDashboard'] );
     Route::get('/student-view-schedules', [StudentController::class, 'viewSchedules'] );

});
