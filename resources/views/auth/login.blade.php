<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <label for="email" class="wb-label block mb-2">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="wb-input w-full" />
            @error('email') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        <div class="mt-5">
            <label for="password" class="wb-label block mb-2">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="wb-input w-full" />
            @error('password') <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        <div class="block mt-5">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded bg-wb-card border-white/20 text-wb-teal focus:ring-wb-teal/30" name="remember">
                <span class="ms-2 text-sm text-white/50 font-body">Remember me</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <a class="text-xs text-white/40 hover:text-wb-teal font-mono uppercase tracking-[0.12em] transition-colors" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif

            <button type="submit" class="wb-btn-primary">Sign In</button>
        </div>
    </form>
</x-guest-layout>
