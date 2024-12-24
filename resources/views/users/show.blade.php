@extends('layouts.base')



@section('content')
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
        </ol>
    </nav>

    <h2>{{ $user->name }}</h2>
    <p>Email: {{ $user->email }}</p>
    <p>Role:
        @foreach($user->roles() as $role)
            {{ $role->name }}
        @endforeach
    </p>



    <!-- Add more fields as needed -->
@endsection
