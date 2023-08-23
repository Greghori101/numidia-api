<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(200);
    }
    public function stats(Request $request)
    {

        $clients = Student::where('active', true);
        $teachers = Teacher::all()->count();
        $users = User::all()->count();

        $budget = 0;
        foreach ($clients as $client) {
            # code...
            $budget +=  $client->plan->price;
        }



        $data = [
            "clients" => $clients->count(),
            "teachers" => $teachers,
            "users"=>$users,
            "budget" => $budget,
        ];
        return response()->json( $data,200);
    }
}
