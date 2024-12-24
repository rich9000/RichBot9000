@extends('layouts.base')


@section('content')
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Name Field -->
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" class="form-control" id="name" value="{{ $user->name }}" required>
            @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email Field -->
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" value="{{$user->email}}" required>
            @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Roles Checkboxes -->
        <div class="mb-3">
            <label class="form-label">Roles</label>
            <div class="roles-container">
                @foreach($roles as $role)
                    <div class="form-check">
                        <input type="checkbox" name="roles[]" class="form-check-input @error('roles') is-invalid @enderror" id="role_{{ $role->id }}" value="{{ $role->id }}"
                            {{ $user->roles->contains('id', $role->id) ? 'checked' : '' }}>
                        <label class="form-check-label" for="role_{{ $role->id }}">{{ $role->name }}</label>
                    </div>
                @endforeach
            </div>
            @error('roles')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>








        <button type="submit" class="btn btn-primary">Update User</button>
    </form>


@endsection
