<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Dashboard sign-in') }} — {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f4f4f5; color: #18181b; }
        .card { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgb(0 0 0 / 0.08); width: 100%; max-width: 380px; }
        h1 { font-size: 1.25rem; margin: 0 0 1rem; }
        label { display: block; font-size: 0.875rem; margin-bottom: 0.35rem; font-weight: 500; }
        input[type="email"], input[type="password"] { width: 100%; box-sizing: border-box; padding: 0.5rem 0.65rem; border: 1px solid #d4d4d8; border-radius: 6px; margin-bottom: 1rem; font-size: 1rem; }
        button { width: 100%; padding: 0.65rem 1rem; background: #d97706; color: #fff; border: none; border-radius: 6px; font-weight: 600; font-size: 1rem; cursor: pointer; }
        button:hover { background: #b45309; }
        .alert { padding: 0.65rem 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.875rem; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        ul.errors { margin: 0 0 1rem; padding-left: 1.25rem; font-size: 0.875rem; color: #991b1b; }
        .hint { margin-top: 1rem; font-size: 0.8125rem; color: #71717a; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('Dashboard sign-in') }}</h1>

        @if (session('dashboard_access_denied'))
            <div class="alert alert-info" role="alert">{{ session('dashboard_access_denied') }}</div>
        @endif

        @if ($errors->any())
            <ul class="errors">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('dashboard.login.authenticate') }}" novalidate>
            @csrf
            <label for="email">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus>

            <label for="password">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">

            <button type="submit">{{ __('Sign in') }}</button>
        </form>

        <p class="hint">{{ __('Staff can also sign in from the platform or restaurant panel login pages.') }}</p>
    </div>
</body>
</html>
