<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Admin;
use App\Models\Lecturer;
use App\Models\Student;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    /**
 * Student login
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function studentLogin(Request $request)
{
    $validator = Validator::make($request->all(), [
        'student_id' => 'required|exists:students,student_id|min:9|max:9',
        'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
            'errors' => $validator->errors(),
        ], 422);
    }

    $credentials = $request->only('student_id', 'password');

    if (Auth::guard('student')->attempt($credentials)) {
        $student = Auth::guard('student')->user();
        $token = $this->generateStudentToken($student);

        return response()->json([
            'success' => true,
            'message' => 'Student logged in successfully',
            'role' => 'student',
            'token' => $token,
            'student' => $student,
        ], 200);
    } else {
        $student_id = $request->input('student_id');
        $student = Student::where('student_id', $student_id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student does not exist',
            ], 401);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password',
            ], 401);
        }
    }
}


/**
 * Student registration
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function studentRegister(Request $request)
{
    $validator = Validator::make($request->all(), [
        'student_id' => 'required|unique:students,student_id|min:9|max:9',
        'name' => 'required|string|max:255',
        'phone' => 'required|string|min:9|max:15',
        'password' => 'required|string|min:6|confirmed',
        'department_id' => 'required|exists:departments,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    $student = new Student();
    $student->student_id = $request->input('student_id');
    $student->name = $request->input('name');
    $student->phone = $request->input('phone');
    $student->password = Hash::make($request->input('password'));
    $student->department_id = $request->input('department_id');
    $student->save();

    $token = $this->generateStudentToken($student);

    return response()->json([
        'success' => true,
        'role' => 'student',
        'message' => 'Student registered successfully',
        'token' => $token,
        'student' => $student,
    ], 201);
}


    /**
 * Lecturer login
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function lecturerLogin(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|exists:lecturers,email',
        'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
            'errors' => $validator->errors(),
        ], 422);
    }

    $credentials = $request->only('email', 'password');

    if (Auth::guard('lecturer')->attempt($credentials)) {
        $lecturer = Auth::guard('lecturer')->user();
        $token = $this->generateLecturerToken($lecturer);

        return response()->json([
            'success' => true,
            'role' => 'lecturer',
            'message' => 'Lecturer logged in successfully',
            'token' => $token,
            'lecturer' => $lecturer,
        ], 200);
    } else {
        $email = $request->input('email');
        $lecturer = Lecturer::where('email', $email)->first();

        if (!$lecturer) {
            return response()->json([
                'success' => false,
                'message' => 'Email does not exist',
            ], 401);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password',
            ], 401);
        }
    }
}

   /**
 * Admin login
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function adminLogin(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
            'errors' => $validator->errors(),
        ], 422);
    }

    $credentials = $request->only('email', 'password');

    if (Auth::guard('admin')->attempt($credentials)) {
        $admin = Auth::guard('admin')->user();
        $token = $this->generateAdminToken($admin);

        return response()->json([
            'success' => true,
            'role' => 'admin',
            'message' => 'Admin logged in successfully',
            'token' => $token,
            'admin' => $admin,
        ], 200);
    } else {
        $email = $request->input('email');
        $admin = Admin::where('email', $email)->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Email does not exist',
            ], 401);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password',
            ], 401);
        }
    }
}


 /**
     * Student logout
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentLogout(Request $request)
    {
        $student = Auth::guard('student')->user();

        if ($student) {
            Auth::guard('student')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Student logged out successfully',
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
    }


    /**
     * Lecturer logout
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lecturerLogout(Request $request)
    {
        $lecturer = Auth::guard('lecturer')->user();

        if ($lecturer) {
            Auth::guard('lecturer')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Lecturer logged out successfully',
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
    }


    /**
     * Admin logout
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminLogout(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            Auth::guard('admin')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Admin logged out successfully',
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
    }

    /**
     * Generate JWT token for the admin user
     *
     * @param  \App\Models\Admin  $admin
     * @return string
     */
    private function generateAdminToken(Admin $admin)
    {
        $token = JWTAuth::fromUser($admin);
        return $token;
    }

     /**
     * Generate JWT token for the lecturer user
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return string
     */
    private function generateLecturerToken(Lecturer $lecturer)
    {
        $token = JWTAuth::fromUser($lecturer);
        return $token;
    }

     /**
     * Generate JWT token for the student user
     *
     * @param  \App\Models\Student  $lecturer
     * @return string
     */
    private function generateStudentToken(Student $student)
    {
        $token = JWTAuth::fromUser($student);
        return $token;
    }
}
