@extends('layouts.app')

@section('title', 'Manage items')
@section('main_class', 'wide')

@section('content')
    <h1>Manage items</h1>

    <div class="table-wrap">
        <table class="table">
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
                        <td><span class="badge {{ $item->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $item->is_active ? 'Active' : 'Deactivated' }}</span></td>
                        <td>
                            <form class="edit-form" method="POST" action="/items/{{ $item->id }}">
                                @csrf
                                <input type="hidden" name="action" value="update">

                                <div class="field-inline">
                                    <label for="name-{{ $item->id }}">Name</label>
                                    <input id="name-{{ $item->id }}" name="name" type="text" value="{{ $item->name }}" maxlength="100" required>
                                </div>

                                <div class="field-inline">
                                    <label for="type-{{ $item->id }}">Type</label>
                                    <select id="type-{{ $item->id }}" name="type" required>
                                        <option value="field" @selected($item->type === 'field')>Field</option>
                                        <option value="equipment" @selected($item->type === 'equipment')>Equipment</option>
                                    </select>
                                </div>

                                <button class="btn btn-secondary btn-sm" type="submit">Save</button>
                            </form>
                        </td>
                        <td class="row-action">
                            @if ($item->is_active)
                                <form method="POST" action="/items/{{ $item->id }}">
                                    @csrf
                                    <input type="hidden" name="action" value="deactivate">
                                    <button class="btn btn-danger btn-sm" type="submit">Deactivate</button>
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
    </div>

    <div class="card">
        <h2>Add item</h2>

        <form method="POST" action="/items">
            @csrf

            <div class="form-grid">
                <div class="field">
                    <label for="new-name">Name</label>
                    <input id="new-name" name="name" type="text" value="{{ old('name') }}" maxlength="100" required>
                </div>

                <div class="field">
                    <label for="new-type">Type</label>
                    <select id="new-type" name="type" required>
                        <option value="field" @selected(old('type') === 'field')>Field</option>
                        <option value="equipment" @selected(old('type') === 'equipment')>Equipment</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Add item</button>
            </div>
        </form>
    </div>
@endsection
