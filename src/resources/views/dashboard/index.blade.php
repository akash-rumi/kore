@extends('layouts.app')

@section('title', 'Admin Dashboard — KoreSearch')

@section('content')

<div class="dashboard-layout">

    <aside class="dashboard-sidebar">
        <nav class="dash-nav">
            <a href="#section-users" class="dash-nav-link active" data-section="section-users">
                <span class="dash-nav-icon">👥</span> Users
            </a>
            <a href="#section-courses" class="dash-nav-link" data-section="section-courses">
                <span class="dash-nav-icon">📚</span> Courses
            </a>
            <a href="#section-orders" class="dash-nav-link" data-section="section-orders">
                <span class="dash-nav-icon">🧾</span> Orders
            </a>
        </nav>
    </aside>

    <div class="dashboard-main">

        <div class="dash-header">
            <h1 class="dash-title">Admin Dashboard</h1>
            <p class="dash-subtitle">Welcome back, {{ Auth::user()->name }}</p>
        </div>

        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <div class="stat-number">{{ $totalUsers }}</div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-info">
                    <div class="stat-number">{{ $courses->count() }}</div>
                    <div class="stat-label">Total Courses</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🧾</div>
                <div class="stat-info">
                    <div class="stat-number">{{ $orders->count() }}</div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
        </div>

        {{-- USERS --}}
        <section class="dash-section" id="section-users">
            <h2 class="dash-section-title">Users</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="role-badge role-{{ $user->role }}">{{ ucfirst($user->role) }}</span></td>
                                <td>{{ $user->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- COURSES --}}
        <section class="dash-section hidden" id="section-courses">
            <h2 class="dash-section-title">Courses</h2>

            <div class="upload-form-box">
                <h3 class="upload-form-title">Upload New Course</h3>
                <p class="upload-form-subtitle">
                    🤖 AI will automatically generate course curriculum topics after upload.
                </p>

                <form method="POST" action="{{ route('dashboard.courses.store') }}" enctype="multipart/form-data" class="upload-form">
                    @csrf

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="title">Course Title</label>
                            <input type="text" name="title" id="title"
                                class="form-input @error('title') is-error @enderror"
                                value="{{ old('title') }}" required>
                            @error('title')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="category">Category</label>
                            <select name="category" id="category"
                                class="form-select @error('category') is-error @enderror" required>
                                <option value="">Select Category</option>
                                @foreach(['Backend','Frontend','Database','Design','DevOps','Mobile','Data Science'] as $cat)
                                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                            @error('category')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    {{-- BUG FIX: level field was missing entirely --}}
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="level">Level</label>
                            <select name="level" id="level"
                                class="form-select @error('level') is-error @enderror" required>
                                <option value="">Select Level</option>
                                <option value="beginner"     {{ old('level') === 'beginner'     ? 'selected' : '' }}>Beginner</option>
                                <option value="intermediate" {{ old('level') === 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                <option value="advanced"     {{ old('level') === 'advanced'     ? 'selected' : '' }}>Advanced</option>
                            </select>
                            @error('level')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="price">Price (BDT)</label>
                            <input type="number" name="price" id="price"
                                class="form-input @error('price') is-error @enderror"
                                value="{{ old('price', 0) }}" min="0" step="1" required>
                            @error('price')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">
                            Description
                            {{-- PHASE 4: AI suggest button --}}
                            <button type="button" id="aiSuggestBtn" class="btn-ai-suggest" title="Generate with AI">
                                ✨ AI Suggest
                            </button>
                        </label>
                        <textarea name="description" id="description"
                            class="form-textarea @error('description') is-error @enderror"
                            maxlength="500" rows="4" required>{{ old('description') }}</textarea>
                        <span class="char-count" id="descCounter">500 characters remaining</span>
                        @error('description')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="thumbnail">Thumbnail (JPG/PNG, max 2MB)</label>
                        <input type="file" name="thumbnail" id="thumbnail"
                            class="form-input-file" accept="image/jpeg,image/png">
                        @error('thumbnail')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary" id="uploadCourseBtn">
                        Upload Course
                    </button>
                </form>
            </div>

            <div class="table-wrapper mt-lg">
                <table class="data-table">
                    <thead>
                        <tr><th>#</th><th>Title</th><th>Category</th><th>Level</th><th>Price</th><th>Enrolled</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $course)
                            <tr>
                                <td>{{ $course->id }}</td>
                                <td><a href="{{ route('courses.show', $course->slug) }}">{{ $course->title }}</a></td>
                                <td>{{ $course->category }}</td>
                                <td>{{ ucfirst($course->level) }}</td>
                                <td>{{ $course->isFree() ? 'Free' : '৳'.number_format($course->price) }}</td>
                                <td>{{ $course->enrolled_count }}</td>
                                <td>
                                    <span class="status-badge {{ $course->is_published ? 'status-completed' : 'status-pending' }}">
                                        {{ $course->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ORDERS --}}
        <section class="dash-section hidden" id="section-orders">
            <h2 class="dash-section-title">Orders</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr><th>Order ID</th><th>Student</th><th>Course</th><th>Transaction #</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $order->user->name ?? '—' }}</td>
                                <td>{{ $order->course->title ?? '—' }}</td>
                                <td class="transaction-number">{{ $order->transaction_number }}</td>
                                <td>৳{{ number_format($order->amount) }}</td>
                                <td><span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                                <td>{{ $order->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty-row">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</div>

<script>
// PHASE 4: AI description suggest
document.getElementById('aiSuggestBtn')?.addEventListener('click', async function () {
    const title    = document.getElementById('title').value.trim();
    const category = document.getElementById('category').value;
    const level    = document.getElementById('level').value;

    if (!title || !category || !level) {
        alert('Please fill in Title, Category and Level first.');
        return;
    }

    this.textContent = '✨ Generating...';
    this.disabled = true;

    try {
        const res = await fetch('{{ route("dashboard.ai.suggest") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ title, category, level }),
        });

        const data = await res.json();
        if (data.description) {
            document.getElementById('description').value = data.description;
            // trigger counter update
            document.getElementById('description').dispatchEvent(new Event('input'));
        }
    } catch (e) {
        alert('AI suggestion failed. Please write a description manually.');
    } finally {
        this.textContent = '✨ AI Suggest';
        this.disabled = false;
    }
});
</script>

@endsection
