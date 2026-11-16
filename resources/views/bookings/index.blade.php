@extends('layouts.app')

@section('title', 'My bookings')
@section('main_class', 'wide')

@section('content')
    <h1>My bookings</h1>

    @if ($bookings === [])
        <div class="empty">
            <p>You have no bookings yet. <a href="/bookings/create">Request a booking</a>.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
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
                            <td><span class="badge badge-{{ $booking['status'] }}">{{ ucfirst($booking['status']) }}</span></td>
                            <td>
                                @if ($booking['can_cancel'])
                                    <form method="POST" action="/bookings/{{ $booking['id'] }}/cancel">
                                        @csrf
                                        <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
