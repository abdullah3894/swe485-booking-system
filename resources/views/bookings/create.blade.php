@extends('layouts.app')

@section('title', 'Request booking')

@section('content')
    <h1>Request a booking</h1>

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Show availability</h2>

    <form method="GET" action="/bookings/create">
        <label for="item">Item</label>
        <select id="item" name="item" required>
            <option value="">Choose an item</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((int) old('item', $selectedItem) === $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>

        <label for="date">Date</label>
        <input id="date" name="date" type="date" value="{{ old('date', $selectedDate) }}" required>

        <button type="submit">Show availability</button>
    </form>

    @if ($selectedItem !== null && $selectedDate !== null)
        <h2>Booked ranges on {{ $selectedDate }}</h2>

        @if ($ranges === [])
            <p>No bookings for this item on this date.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th scope="col">Start</th>
                        <th scope="col">End</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ranges as $range)
                        <tr>
                            <td>{{ $range['start'] }}</td>
                            <td>{{ $range['end'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    @php
        $formItem = old('item_id', $selectedItem);
        $formDate = old('date', $selectedDate);
    @endphp

    @if ($formItem !== null && $formDate !== null)
        <h2>Request a time</h2>

        <form method="POST" action="/bookings">
            @csrf
            <input type="hidden" name="item_id" value="{{ $formItem }}">
            <input type="hidden" name="date" value="{{ $formDate }}">

            <label for="start_time">Start time</label>
            <input id="start_time" name="start_time" type="time" value="{{ old('start_time') }}" min="06:00" max="23:00" required>

            <label for="end_time">End time</label>
            <input id="end_time" name="end_time" type="time" value="{{ old('end_time') }}" min="06:00" max="23:00" required>

            <button type="submit">Send request</button>
        </form>
    @endif
@endsection
