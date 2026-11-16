@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <div class="card auth-card">
        <h1>Log in</h1>

        <form method="POST" action="/login">
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Log in</button>
            </div>
        </form>

        <p class="hint">No account yet? <a class="btn btn-secondary btn-sm" href="/register">Register</a></p>
    </div>
@endsection
