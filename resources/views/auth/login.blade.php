@extends('layouts.guest')

@section('content')

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">

                <div class="card">
                    <div class="card-body p-4 p-sm-5">
                        <div class="row justify-content-between mb-2">
                            <div class="col-auto">
                                <h5>{{ __('Login') }}</h5>
                            </div>
                            <div class="col-auto fs-6 text-muted">
                                <span>{{ __('or') }}</span>
                                <span><a href="{{ url('register') }}">{{ __('Create an account') }}</a></span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">{{ __('E-Mail Address') }}</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="Email address">
                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">{{ __('Password') }}</label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Password">
                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="row justify-content-between">
                                <div class="col-auto">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="remember-me" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="remember-me">{{ __('Remember Me') }}</label>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    @if (Route::has('password.request'))
                                        <a class="fs-6" href="{{ route('password.request') }}">{{ __('Forgot Your Password?') }}</a>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary w-100 mt-3" type="submit">{{ __('Login') }}</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection
