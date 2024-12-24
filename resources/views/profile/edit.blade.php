@extends('layouts.base')

@section('content')
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Profile') }}
                </h2>
                <p>This will be a page for the user to make changes that affect their user. This could be a tab'd page with different user profile settings on each tab.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8 mx-auto">
                <div class="card mb-4">
                    <div class="card-body">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>

                <!-- New Notification Card -->
                <div class="card mb-4">
                    <div class="card-body">
                @include('profile.partials.user-notifications')

                    </div>
                </div>























            </div>
        </div>
    </div>
@endsection
