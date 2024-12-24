@extends('layouts.base')

@section('content')
    <div class="container mt-4">
        <h1>Create Assistant Function</h1>
        @include('assistant_functions.content._form', ['action' => route('assistant_functions.store'), 'method' => 'POST'])
    </div>
@endsection
