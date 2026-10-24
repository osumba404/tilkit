@if ($errors->any())
    <div class="{{ $wrapperClass ?? 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5' }}">
        <div class="tk-error-banner" role="alert">
            <p class="font-medium">Please fix the following:</p>
            <ul class="mt-2 list-disc pl-5 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
