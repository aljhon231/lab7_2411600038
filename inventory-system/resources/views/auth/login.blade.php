<x-guest-layout>
    <div class="text-center mb-4">
        <h2 class="h3 fw-bold mb-2">Welcome back</h2>
        <p class="text-muted mb-0">Sign in to manage your inventory.</p>
    </div>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <label for="remember_me" class="form-check-label d-flex align-items-center gap-2">
                <input id="remember_me" class="form-check-input m-0" type="checkbox" name="remember">
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>
        <button type="submit" class="btn login-btn w-100">Sign in</button>
    </form>

    @if (Route::has('register'))
        <p class="text-center text-muted mt-4 mb-0">
            No account yet? <a href="{{ route('register') }}">Create an account</a>
        </p>
    @endif
</x-guest-layout>
