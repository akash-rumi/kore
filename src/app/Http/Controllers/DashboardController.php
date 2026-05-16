<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        // BUG FIX: added eager loading to prevent N+1 queries
        $totalUsers = User::count();
        $users = User::latest()->get();
        $courses = Course::with('instructor')->latest()->get();
        $orders = Order::with(['user', 'course'])->latest()->get();

        return view('dashboard.index', compact('totalUsers', 'users', 'courses', 'orders'));
    }

    public function storeCourse(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'string'],
            // BUG FIX: level was missing from validation — always defaulted to 'beginner'
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $thumbnailPath = null;

        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        Course::create([
            'instructor_id' => Auth::id(),
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . Str::random(4),
            'description' => $request->description,
            'price' => $request->price,
            'category' => $request->category,
            // BUG FIX: level now saved from form input
            'level' => $request->level,
            'thumbnail' => $thumbnailPath,
            'is_published' => true,
        ]);

        return redirect()->route('dashboard')->with('success', 'Course uploaded successfully.');
    }
}
