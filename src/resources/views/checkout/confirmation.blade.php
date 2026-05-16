@extends('layouts.app')

@section('title', 'Order Confirmed — KoreSearch')

@section('content')

<div class="container">
    <div class="confirmation-box">
        <div class="confirmation-icon">✅</div>
        <h1 class="confirmation-title">Payment Received!</h1>
        <p class="confirmation-subtitle">Thank you for your purchase. Your order has been placed successfully.</p>

        <div class="confirmation-details">
            <div class="confirmation-row">
                <span class="conf-label">Order ID</span>
                <span class="conf-value">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="confirmation-row">
                <span class="conf-label">Course</span>
                <span class="conf-value">{{ $order->course->title }}</span>
            </div>
            <div class="confirmation-row">
                <span class="conf-label">Amount Paid</span>
                <span class="conf-value">৳{{ number_format($order->amount) }}</span>
            </div>
            <div class="confirmation-row">
                <span class="conf-label">Transaction Number</span>
                <span class="conf-value transaction-number">{{ $order->transaction_number }}</span>
            </div>
            <div class="confirmation-row">
                <span class="conf-label">Status</span>
                <span class="conf-value">
                    <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                </span>
            </div>
            <div class="confirmation-row">
                <span class="conf-label">Date</span>
                <span class="conf-value">{{ $order->created_at->format('d M Y, h:i A') }}</span>
            </div>
        </div>

        <p class="confirmation-note">
            Our team will verify your bKash transaction and activate your course within 24 hours.
        </p>

        <div class="confirmation-actions">
            <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse More Courses</a>
            {{-- BUG FIX: was route('dashboard') — students get 403. Now role-based. --}}
            @if(Auth::user()->isAdmin())
                <a href="{{ route('dashboard') }}" class="btn btn-outline">Admin Dashboard</a>
            @else
                <a href="{{ route('student.dashboard') }}" class="btn btn-outline">My Learning</a>
            @endif
        </div>
    </div>
</div>

@endsection
