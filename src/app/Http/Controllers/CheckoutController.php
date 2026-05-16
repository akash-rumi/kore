<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function index()
    {
        $cartIds = session()->get('cart', []);

        if (empty($cartIds)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $courses = Course::whereIn('id', $cartIds)->get();
        $total = $courses->sum('price');

        return view('checkout.index', compact('courses', 'total'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'course_id'          => ['required', 'exists:courses,id'],
            'transaction_number' => ['required', 'string', 'max:255'],
        ]);

        $course = Course::findOrFail($request->course_id);

        $order = Order::create([
            'user_id'            => Auth::id(),
            'course_id'          => $request->course_id,
            'transaction_number' => $request->transaction_number,
            'amount'             => $course->price,
            'status'             => 'pending',
        ]);

        // BUG FIX: clear cart after successful checkout
        session()->forget('cart');

        return redirect()->route('checkout.confirmation', $order->id);
    }
    public function confirmation(Order $order)
    {
        $order->load('course', 'user');
        return view('checkout.confirmation', compact('order'));
    }
}
