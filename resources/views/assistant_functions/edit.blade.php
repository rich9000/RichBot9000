@extends('layouts.base')

@section('content')
    <div class="container mt-4">
        <h1>Edit Assistant Function</h1>
        @include('assistant_functions.content._form', ['action' => route('assistant_functions.update', $assistantFunction), 'method' => 'PUT', 'assistantFunction' => $assistantFunction])
    </div>
@endsection
