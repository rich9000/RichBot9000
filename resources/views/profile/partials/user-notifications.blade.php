<!-- New Notification Card -->
<section class="mb-4">
    <header>
        <h2 class="h5 font-weight-bold text-dark">
            {{ __('Notification Settings') }}
        </h2>

        <p class="mt-2 text-muted">
            {{ __('Manage your notification preferences.') }}
        </p>
    </header>

    <form method="post" action="" class="mt-4">
        @csrf
        @method('patch')

        <!-- Email Notifications -->
        <div class="mb-3">
            <label for="email_notifications" class="form-label">{{ __('Email Notifications') }}</label>
            <select id="email_notifications" name="email_notifications" class="form-select @error('email_notifications') is-invalid @enderror">
                <option value="enabled" {{ old('email_notifications', $user->email_notifications) == 'enabled' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                <option value="disabled" {{ old('email_notifications', $user->email_notifications) == 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
            </select>
            @error('email_notifications')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror
        </div>

        <!-- SMS Notifications -->
        <div class="mb-3">
            <label for="sms_notifications" class="form-label">{{ __('SMS Notifications') }}</label>
            <select id="sms_notifications" name="sms_notifications" class="form-select @error('sms_notifications') is-invalid @enderror">
                <option value="enabled" {{ old('sms_notifications', $user->sms_notifications) == 'enabled' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                <option value="disabled" {{ old('sms_notifications', $user->sms_notifications) == 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
            </select>
            @error('sms_notifications')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror
        </div>

        <!-- New Event Notifications -->
        <div class="mb-3">
            <label for="new_event_notifications" class="form-label">{{ __('New Event Notifications') }}</label>
            <select id="new_event_notifications" name="new_event_notifications" class="form-select @error('new_event_notifications') is-invalid @enderror">
                <option value="enabled" {{ old('new_event_notifications', $user->new_event_notifications) == 'enabled' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                <option value="disabled" {{ old('new_event_notifications', $user->new_event_notifications) == 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
            </select>
            @error('new_event_notifications')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror
        </div>

        <!-- New Session Notifications -->
        <div class="mb-3">
            <label for="new_session_notifications" class="form-label">{{ __('New Session Notifications') }}</label>
            <select id="new_session_notifications" name="new_session_notifications" class="form-select @error('new_session_notifications') is-invalid @enderror">
                <option value="enabled" {{ old('new_session_notifications', $user->new_session_notifications) == 'enabled' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                <option value="disabled" {{ old('new_session_notifications', $user->new_session_notifications) == 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
            </select>
            @error('new_session_notifications')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror
        </div>

        <!-- New User Notifications -->
        <div class="mb-3">
            <label for="new_user_notifications" class="form-label">{{ __('New User Notifications') }}</label>
            <select id="new_user_notifications" name="new_user_notifications" class="form-select @error('new_user_notifications') is-invalid @enderror">
                <option value="enabled" {{ old('new_user_notifications', $user->new_user_notifications) == 'enabled' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                <option value="disabled" {{ old('new_user_notifications', $user->new_user_notifications) == 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
            </select>
            @error('new_user_notifications')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
            @enderror
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

            @if (session('status') === 'notifications-updated')
                <p
                    class="text-success ms-3"
                    id="notifications-updated-message"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
