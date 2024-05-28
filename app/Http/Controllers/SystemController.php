<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
class SystemController extends Controller
{
    public function fetchDepartments()
{
    try {
        $departments = Department::select('id', 'name')->get();

        return response()->json([
            'success' => true,
            'data' => $departments,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve departments',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
