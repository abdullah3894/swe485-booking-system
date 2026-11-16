@extends('layouts.app')

@section('title', 'Supervisor dashboard')
@section('main_class', 'wide')

@section('content')
    <h1>Supervisor dashboard</h1>

    <div class="card">
        <form class="inline-form" method="GET" action="/dashboard">
            <div class="field">
                <label for="date">Date</label>
                <input id="date" name="date" type="date" value="{{ $date }}">
            </div>
            <button class="btn btn-primary" type="submit">Show day</button>
        </form>
    </div>

    <h2>Pending requests</h2>

    @if ($schedule['pending'] === [])
        <p class="empty">No pending requests.</p>
    @else
        <div class="table-wrap">
            <table class="table">
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
                                    <button class="btn btn-primary btn-sm" type="submit" name="decision" value="approve">Approve</button>
                                    <button class="btn btn-secondary btn-sm" type="submit" name="decision" value="reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2>Schedule for {{ $date }}</h2>

    @if ($schedule['day'] === [])
        <p class="empty">No bookings on this day.</p>
    @else
        <div class="table-wrap">
            <table class="table">
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
                            <td><span class="badge badge-{{ $booking['status'] }}">{{ ucfirst($booking['status']) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2>Availability for {{ $date }}</h2>

    @if ($schedule['availability'] === [])
        <p class="empty">No active items.</p>
    @else
        <div class="availability">
            @foreach ($schedule['availability'] as $item)
                <div class="availability-item">
                    <p class="availability-name">{{ $item['item_name'] }}</p>

                    @include('partials.timeline', ['segments' => $item['booked']])

                    <dl class="availability-ranges">
                        <dt>Booked</dt>
                        <dd>@forelse ($item['booked'] as $range){{ $range['start'] }}-{{ $range['end'] }}@if (! $loop->last), @endif @empty None @endforelse</dd>

                        <dt>Free</dt>
                        <dd>@forelse ($item['free'] as $range){{ $range['start'] }}-{{ $range['end'] }}@if (! $loop->last), @endif @empty None @endforelse</dd>
                    </dl>
                </div>
            @endforeach
        </div>
    @endif
@endsection
