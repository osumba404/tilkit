<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold text-tk-ink">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-3xl space-y-6">
            <div class="tk-card p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="tk-card p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="tk-card p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
