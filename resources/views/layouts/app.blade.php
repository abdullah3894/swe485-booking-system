<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f6f6f4; color: #1c1c1a; }
        header { display: flex; align-items: center; gap: 1rem; padding: .75rem 1.5rem; background: #fff; border-bottom: 1px solid #dcdcd6; font-size: .875rem; }
        header nav { margin-left: auto; display: flex; align-items: center; gap: 1rem; }
        header form { margin: 0; }
        header button { background: none; color: #1c1c1a; text-decoration: underline; padding: 0; }
        main { max-width: 24rem; margin: 4rem auto; padding: 2rem; background: #fff; border: 1px solid #dcdcd6; border-radius: 6px; }
        main.wide { max-width: 56rem; }
        h1 { font-size: 1.25rem; margin: 0 0 1.25rem; }
        h2 { font-size: 1rem; margin: 2rem 0 1rem; }
        label { display: block; font-size: .875rem; margin-bottom: .25rem; }
        input { width: 100%; box-sizing: border-box; padding: .5rem; margin-bottom: 1rem; border: 1px solid #b9b9b1; border-radius: 4px; font: inherit; }
        select { width: 100%; box-sizing: border-box; padding: .5rem; margin-bottom: 1rem; border: 1px solid #b9b9b1; border-radius: 4px; font: inherit; background: #fff; }
        button { padding: .5rem 1rem; font: inherit; border: 0; border-radius: 4px; background: #1c1c1a; color: #fff; cursor: pointer; }
        ul.errors { margin: 0 0 1rem; padding: .75rem 1rem .75rem 2rem; background: #fdf0ef; border: 1px solid #e3b5b0; border-radius: 4px; font-size: .875rem; }
        p.hint { font-size: .875rem; margin-top: 1.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        th, td { text-align: left; padding: .5rem; border-bottom: 1px solid #dcdcd6; vertical-align: top; }
        td input, td select { margin-bottom: .5rem; }
        td form { margin: 0; }
    </style>
</head>
<body>
    @auth
        <header>
            <span>{{ auth()->user()->name }}</span>
            <nav>
                @if (auth()->user()->isSupervisor())
                    <a href="/dashboard">Dashboard</a>
                    <a href="/items">Items</a>
                @endif
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit">Log out</button>
                </form>
            </nav>
        </header>
    @endauth
    <main class="@yield('main_class')">
        @yield('content')
    </main>
</body>
</html>
