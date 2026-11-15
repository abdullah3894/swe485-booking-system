@extends('layouts.app')

@section('title', 'Supervisor dashboard')
@section('main_class', 'wide')

@section('content')
    <h1>Supervisor dashboard</h1>

    @if (session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    @if (session('error'))
        <ul class="errors">
            <li>{{ session('error') }}</li>
        </ul>
    @endif

    <form method="GET" action="/dashboard">
        <label for="date">Date</label>
        <input id="date" name="date" type="date" value="{{ $date }}">
        <button type="submit">Show day</button>
    </form>

    <h2>Pending requests</h2>

    @if ($schedule['pending'] === [])
        <p>No pending requests.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th scope="col">Item</th>
                    <th scope="col">Member</th>
                    <th scope="col">Date</th>
                    <th scope="col">Start</th>
                    <th scope="col">End</th>
                    <th scope="col">Decision</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schedule['pending'] as $booking)
                    <tr>
                        <td>{{ $booking['item_name'] }}</td>
                        <td>{{ $booking['member_name'] }}</td>
                        <td>{{ $booking['date'] }}</td>
                        <td>{{ $booking['start'] }}</td>
                        <td>{{ $booking['end'] }}</td>
                        <td>
                            <form method="POST" action="/dashboard/bookings/{{ $booking['id'] }}/decision">
                                @csrf
                                <input type="hidden" name="date" value="{{ $date }}">
                                <button type="submit" name="decision" value="approve">Approve</button>
                                <button type="submit" name="decision" value="reject">Reject</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Schedule for {{ $date }}</h2>

    @if ($schedule['day'] === [])
        <p>No bookings on this day.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th scope="col">Item</th>
                    <th scope="col">Member</th>
                    <th scope="col">Start</th>
                    <th scope="col">End</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schedule['day'] as $booking)
                    <tr>
                        <td>{{ $booking['item_name'] }}</td>
                        <td>{{ $booking['member_name'] }}</td>
                        <td>{{ $booking['start'] }}</td>
                        <td>{{ $booking['end'] }}</td>
                        <td>{{ ucfirst($booking['status']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Availability for {{ $date }}</h2>

    @if ($schedule['availability'] === [])
        <p>No active items.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th scope="col">Item</th>
                    <th scope="col">Booked</th>
                    <th scope="col">Free</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($schedule['availability'] as $item)
                    <tr>
                        <td>{{ $item['item_name'] }}</td>
                        <td>
                            @forelse ($item['booked'] as $range)
                                {{ $range['start'] }}-{{ $range['end'] }}@if (! $loop->last), @endif
                            @empty
                                None
                            @endforelse
                        </td>
                        <td>
                            @forelse ($item['free'] as $range)
                                {{ $range['start'] }}-{{ $range['end'] }}@if (! $loop->last), @endif
                            @empty
                                None
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
