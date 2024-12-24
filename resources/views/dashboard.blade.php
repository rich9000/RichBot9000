@extends('layouts.base')

@section('content')
    <div class="container py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="font-weight-semibold text-xl text-gray-800 leading-tight">
                            {{ __('Dashboard') }} (Everyone gets this section)

                        </div>
                    </div>

                    <div class="card-body">
                        <div class="alert alert-success" role="alert">
                            {{ __("You're logged in!") }}
                        </div>

                    </div>
                </div>
            </div>

            @if(auth()->user()->roles->contains('name','Admin'))
            <div class="col-md-12 mt-3">
                <div class="card">
                    <div class="card-header">
                        <div class="font-weight-semibold text-xl text-gray-800 leading-tight">
                           Admin (Role Admin only section)


                        </div>
                    </div>

                    <div class="card-body">


                        <a href="{{route('users.index')}}">Users</a><br/>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
