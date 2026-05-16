<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with('instructor')->where('is_published', true);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $courses = $query->latest()->paginate(9);

        $categories = Cache::remember('course_categories', 3600, function () {
            return Course::where('is_published', true)
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category');
        });

        return view('courses.index', compact('courses', 'categories'));
    }

    public function show($slug)
    {
        $course = Course::with('instructor')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('courses.show', compact('course'));
    }
}
