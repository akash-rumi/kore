<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cartIds = session()->get('cart', []);
        $courses = Course::whereIn('id', $cartIds)->get();
        $total = $courses->sum('price');

        return view('cart.index', compact('courses', 'total'));
    }

    public function add(Request $request, Course $course)
    {
        $cart = session()->get('cart', []);

        if (!in_array($course->id, $cart)) {
            $cart[] = $course->id;
            session()->put('cart', $cart);
        }

        // BUG FIX: was returning count BEFORE adding — used $countBeforeUpdate = count($cart) - 1
        // Now returns actual cart count after update
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'count' => count($cart),
                'message' => 'Course added to cart.',
            ]);
        }

        return redirect()->back()->with('success', 'Course added to cart.');
    }

    public function remove(Request $request, Course $course)
    {
        $cart = session()->get('cart', []);

        // BUG FIX: session stores int IDs, comparison must cast consistently
        $cart = array_filter($cart, fn($id) => (int) $id !== (int) $course->id);
        session()->put('cart', array_values($cart));

        return redirect()->route('cart.index')->with('success', 'Course removed from cart.');
    }
}
