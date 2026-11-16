@extends('layouts.app')

@section('title', 'Request booking')
@section('main_class', 'wide')

@section('content')
    <h1>Request a booking</h1>

    <div class="card">
        <p class="card-eyebrow">1. Choose item and date</p>
        <h2>Show availability</h2>

        <form method="GET" action="/bookings/create">
            <div class="form-grid">
                <div class="field">
                    <label for="item">Item</label>
                    <select id="item" name="item" required>
                        <option value="">Choose an item</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" @selected((int) old('item', $selectedItem) === $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="date">Date</label>
                    <input id="date" name="date" type="date" value="{{ old('date', $selectedDate) }}" required>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Show availability</button>
            </div>
        </form>

        @if ($selectedItem !== null && $selectedDate !== null)
            <section class="availability-preview">
                <h3>Booked ranges on {{ $selectedDate }}</h3>

                @if ($ranges === [])
                    <p class="empty">No bookings for this item on this date.</p>
                @else
                    @include('partials.timeline', ['segments' => $ranges])

                    <div class="table-wrap">
                        <table class="table">
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
                    </div>
                @endif
            </section>
        @endif
    </div>

    @php
        $formItem = old('item_id', $selectedItem);
        $formDate = old('date', $selectedDate);
    @endphp

    @if ($formItem !== null && $formDate !== null)
        <div class="card">
            <p class="card-eyebrow">2. Choose a time</p>
            <h2>Request a time</h2>

            <form method="POST" action="/bookings">
                @csrf
                <input type="hidden" name="item_id" value="{{ $formItem }}">
                <input type="hidden" name="date" value="{{ $formDate }}">

                <div class="form-grid">
                    <div class="field">
                        <label for="start_time">Start time</label>
                        <input id="start_time" name="start_time" type="time" value="{{ old('start_time') }}" min="06:00" max="23:00" required>
                    </div>

                    <div class="field">
                        <label for="end_time">End time</label>
                        <input id="end_time" name="end_time" type="time" value="{{ old('end_time') }}" min="06:00" max="23:00" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Send request</button>
                </div>
            </form>
        </div>
    @endif
@endsection
