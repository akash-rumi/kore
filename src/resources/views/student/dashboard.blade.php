@extends('layouts.app')

@section('title', 'My Learning — KoreSearch')

@section('content')

<div class="page-header">
    <div class="container">
        <h1 class="page-title">My Learning</h1>
        <p class="page-subtitle">Welcome back, {{ $user->name }}</p>
    </div>
</div>

<div class="container">

    <div class="student-dashboard-grid">

        <div class="student-profile-card">
            <h2 class="student-name">{{ $user->name }}</h2>
            <p class="student-email">{{ $user->email }}</p>
            @if($user->headline)
                <p class="student-headline">{{ $user->headline }}</p>
            @endif
            @if($user->location)
                <p class="student-location">{{ $user->location }}</p>
            @endif
            <div class="student-stats">
                <div class="student-stat">
                    <strong>{{ $orders->count() }}</strong>
                    <span>Courses Enrolled</span>
                </div>
                <div class="student-stat">
                    <strong>{{ $orders->where('status', 'completed')->count() }}</strong>
                    <span>Completed</span>
                </div>
            </div>
        </div>

        <div class="student-orders-section">
            <h2 class="dash-section-title">My Enrolled Courses</h2>

            @forelse($orders as $order)
                <div class="student-course-card">
                    <img
                        src="{{ $order->course->thumbnail ?? 'https://placehold.co/80x56' }}"
                        alt="{{ $order->course->title }}"
                        class="student-course-thumb"
                        onerror="this.src='https://placehold.co/80x56'"
                    >
                    <div class="student-course-info">
                        <h3 class="student-course-title">
                            <a href="{{ route('courses.show', $order->course->slug) }}">
                                {{ $order->course->title }}
                            </a>
                        </h3>
                        <p class="student-course-meta">
                            <span class="badge badge-{{ $order->course->level }}">{{ ucfirst($order->course->level) }}</span>
                            · {{ $order->course->category }}
                            · {{ $order->course->duration ?? 'Self-paced' }}
                        </p>
                        <p class="student-order-meta">
                            Order #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                            · {{ $order->created_at->format('d M Y') }}
                        </p>
                    </div>
                    <div class="student-course-status">
                        <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                        <p class="student-course-price">
                            {{ $order->course->isFree() ? 'Free' : '৳'.number_format($order->amount) }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="empty-state-box">
                    <p>You haven't enrolled in any courses yet.</p>
                    <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse Courses</a>
                </div>
            @endforelse
        </div>

    </div>
</div>

@endsection
