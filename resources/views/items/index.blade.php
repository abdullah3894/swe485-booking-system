@extends('layouts.app')

@section('title', 'Manage items')
@section('main_class', 'wide')

@section('content')
    <h1>Manage items</h1>

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <table>
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Type</th>
                <th scope="col">Status</th>
                <th scope="col">Edit</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ ucfirst($item->type) }}</td>
                    <td>{{ $item->is_active ? 'Active' : 'Deactivated' }}</td>
                    <td>
                        <form method="POST" action="/items/{{ $item->id }}">
                            @csrf
                            <input type="hidden" name="action" value="update">

                            <label for="name-{{ $item->id }}">Name</label>
                            <input id="name-{{ $item->id }}" name="name" type="text" value="{{ $item->name }}" maxlength="100" required>

                            <label for="type-{{ $item->id }}">Type</label>
                            <select id="type-{{ $item->id }}" name="type" required>
                                <option value="field" @selected($item->type === 'field')>Field</option>
                                <option value="equipment" @selected($item->type === 'equipment')>Equipment</option>
                            </select>

                            <button type="submit">Save</button>
                        </form>
                    </td>
                    <td>
                        @if ($item->is_active)
                            <form method="POST" action="/items/{{ $item->id }}">
                                @csrf
                                <input type="hidden" name="action" value="deactivate">
                                <button type="submit">Deactivate</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No items yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Add item</h2>

    <form method="POST" action="/items">
        @csrf

        <label for="new-name">Name</label>
        <input id="new-name" name="name" type="text" value="{{ old('name') }}" maxlength="100" required>

        <label for="new-type">Type</label>
        <select id="new-type" name="type" required>
            <option value="field" @selected(old('type') === 'field')>Field</option>
            <option value="equipment" @selected(old('type') === 'equipment')>Equipment</option>
        </select>

        <button type="submit">Add item</button>
    </form>
@endsection
