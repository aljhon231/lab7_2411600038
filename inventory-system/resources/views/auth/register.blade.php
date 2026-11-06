<x-guest-layout>
    <div class="text-center mb-4">
        <h2 class="h3 fw-bold mb-2">Create your account</h2>
        <p class="text-muted mb-0">Register to start managing inventory.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn login-btn w-100">Create account</button>
    </form>

    <p class="text-center text-muted mt-4 mb-0">
        Already registered? <a href="{{ route('login') }}">Sign in</a>
    </p>
</x-guest-layout>
