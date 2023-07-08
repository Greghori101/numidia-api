<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::all();

        return response()->json($departments, 200);
    }
    public function show($id)
    {
        $department = Department::find($id);

        return response()->json($department, 200);
    }
}
