<x-guest-layout>
    <x-auth-heading title="{{ __('Verify your email') }}">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </x-auth-heading>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 tk-status-banner text-sm">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="tk-link">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
