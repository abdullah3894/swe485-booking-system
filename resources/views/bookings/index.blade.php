@extends('layouts.app')

@section('title', 'My bookings')
@section('main_class', 'wide')

@section('content')
    <h1>My bookings</h1>

    @if (session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    @if (session('error'))
        <ul class="errors">
            <li>{{ session('error') }}</li>
        </ul>
    @endif

    @if ($bookings === [])
        <p>You have no bookings yet. <a href="/bookings/create">Request a booking</a>.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th scope="col">Item</th>
                    <th scope="col">Date</th>
                    <th scope="col">Start</th>
                    <th scope="col">End</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $booking)
                    <tr>
                        <td>{{ $booking['item_name'] }}</td>
                        <td>{{ $booking['date'] }}</td>
                        <td>{{ $booking['start'] }}</td>
                        <td>{{ $booking['end'] }}</td>
                        <td>{{ ucfirst($booking['status']) }}</td>
                        <td>
                            @if ($booking['can_cancel'])
                                <form method="POST" action="/bookings/{{ $booking['id'] }}/cancel">
                                    @csrf
                                    <button type="submit">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
