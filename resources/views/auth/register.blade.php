@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="card auth-card">
        <h1>Register</h1>

        <form method="POST" action="/register">
            @csrf

            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Register</button>
            </div>
        </form>

        <p class="hint">Already registered? <a class="btn btn-secondary btn-sm" href="/login">Log in</a></p>
    </div>
@endsection
