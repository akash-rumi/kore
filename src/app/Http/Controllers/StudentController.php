<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $orders = $user->orders()->with('course')->latest()->get();
        return view('student.dashboard', compact('user', 'orders'));
    }
}