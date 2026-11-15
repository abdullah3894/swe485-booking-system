<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #f6f6f4; color: #1c1c1a; }
        main { max-width: 24rem; margin: 4rem auto; padding: 2rem; background: #fff; border: 1px solid #dcdcd6; border-radius: 6px; }
        h1 { font-size: 1.25rem; margin: 0 0 1.25rem; }
        label { display: block; font-size: .875rem; margin-bottom: .25rem; }
        input { width: 100%; box-sizing: border-box; padding: .5rem; margin-bottom: 1rem; border: 1px solid #b9b9b1; border-radius: 4px; font: inherit; }
        button { padding: .5rem 1rem; font: inherit; border: 0; border-radius: 4px; background: #1c1c1a; color: #fff; cursor: pointer; }
        ul.errors { margin: 0 0 1rem; padding: .75rem 1rem .75rem 2rem; background: #fdf0ef; border: 1px solid #e3b5b0; border-radius: 4px; font-size: .875rem; }
        p.hint { font-size: .875rem; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <main>
        @yield('content')
    </main>
</body>
</html>
