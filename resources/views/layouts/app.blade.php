<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <header class="topbar">
        <span class="brand">Sports Booking</span>

        @auth
            <nav class="topbar-nav">
                @if (auth()->user()->isSupervisor())
                    <a class="topbar-link @if (request()->is('dashboard')) is-current @endif" href="/dashboard">Dashboard</a>
                    <a class="topbar-link @if (request()->is('items')) is-current @endif" href="/items">Items</a>
                @else
                    <a class="topbar-link @if (request()->is('bookings/create')) is-current @endif" href="/bookings/create">Request booking</a>
                    <a class="topbar-link @if (request()->is('bookings')) is-current @endif" href="/bookings">My bookings</a>
                @endif

                <span class="topbar-user">{{ auth()->user()->name }}</span>

                <form method="POST" action="/logout">
                    @csrf
                    <button class="btn btn-secondary btn-sm" type="submit">Log out</button>
                </form>
            </nav>
        @endauth
    </header>

    <main class="page @yield('main_class')">
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error" role="alert">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
