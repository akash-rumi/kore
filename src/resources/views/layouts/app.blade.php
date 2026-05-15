<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf" content="{{ csrf_token() }}">
    <title>@yield('title', 'KoreSearch') — Learn & Grow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<nav class="navbar" id="navbar">
    <div class="navbar-inner">
        <a href="{{ route('home') }}" class="navbar-brand">
            <span class="brand-icon">K</span>
            KoreSearch
        </a>

        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
            <li><a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses.*') ? 'active' : '' }}">Courses</a></li>
            @auth
                <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? 'active' : '' }}">Dashboard</a></li>
            @endauth
            <li>
                <a href="{{ route('cart.index') }}" class="cart-link {{ request()->routeIs('cart.*') ? 'active' : '' }}">
                    Cart
                    <span class="cart-badge" id="cartCount">{{ count(session()->get('cart', [])) }}</span>
                </a>
            </li>
            @guest
                <li><a href="{{ route('login') }}" class="btn-nav">Login</a></li>
                <li><a href="{{ route('register') }}" class="btn-nav btn-nav-accent">Register</a></li>
            @else
                <li class="nav-user" id="navUser">
                    <button class="user-avatar-btn" id="userAvatarBtn">
                        <span class="user-avatar-circle">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        <span class="user-name-short">{{ Auth::user()->name }}</span>
                        <span class="dropdown-caret">▾</span>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <strong>{{ Auth::user()->name }}</strong>
                            <small>{{ Auth::user()->email }}</small>
                        </div>
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-logout">Logout</button>
                        </form>
                    </div>
                </li>
            @endguest
        </ul>
    </div>
</nav>

<div class="page-wrapper">

    @if(session('success'))
        <div class="alert alert-success" id="flashAlert">
            <span>{{ session('success') }}</span>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error" id="flashAlert">
            <span>{{ session('error') }}</span>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info" id="flashAlert">
            <span>{{ session('info') }}</span>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    @yield('content')

</div>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="brand-icon">K</span>
            <span>KoreSearch</span>
        </div>
        <p class="footer-copy">© {{ date('Y') }} koresearch.com — All rights reserved.</p>
        <nav class="footer-links">
            <a href="{{ route('home') }}">Home</a>
            <a href="{{ route('courses.index') }}">Courses</a>
            <a href="{{ route('login') }}">Login</a>
        </nav>
    </div>
</footer>

<script src="{{ asset('js/application.js') }}"></script>
</body>
</html>
