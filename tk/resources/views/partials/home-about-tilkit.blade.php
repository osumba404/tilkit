<section aria-labelledby="home-about-heading" class="border-t border-tk-line/80 pt-12 sm:pt-16 pb-6 sm:pb-12" id="about-tilkit">
    <h2 id="home-about-heading" class="font-display text-2xl sm:text-3xl font-bold text-tk-ink max-w-3xl">
        About TILKIT
    </h2>
    <p class="mt-4 text-tk-muted max-w-3xl leading-relaxed">
        <abbr title="Traditional Indigenous and Local Knowledge Tool">TILKIT</abbr>{{ Str::after($about['intro'], 'TILKIT') }}
        @if (! ($weatherTool ?? null))
            <span class="block mt-3">{{ $about['platform_note'] }}</span>
        @endif
    </p>

    <div class="mt-10 grid gap-10 lg:grid-cols-2 lg:gap-12">
        <div>
            <h3 class="font-display text-lg font-semibold text-tk-ink">Origins and history</h3>
            @foreach ($about['origins'] as $paragraph)
                <p class="mt-3 text-sm text-tk-muted leading-relaxed">{{ $paragraph }}</p>
            @endforeach
        </div>

        <div>
            <h3 class="font-display text-lg font-semibold text-tk-ink">Where it has been implemented</h3>
            <ul class="mt-3 space-y-3 text-sm text-tk-muted leading-relaxed list-disc pl-5 marker:text-tk-accent">
                @foreach ($about['implementations'] as $item)
                    <li>
                        <strong class="font-medium text-tk-ink">{{ $item['title'] }}:</strong>
                        {{ $item['text'] }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <aside aria-labelledby="home-timeline-heading" class="mt-10 tk-card p-6 sm:p-8">
        <h3 id="home-timeline-heading" class="font-display text-lg font-semibold text-tk-ink">Timeline at a glance</h3>
        <ol class="mt-4 space-y-4 text-sm text-tk-muted border-l-2 border-tk-accent/30 pl-5">
            @foreach ($about['timeline'] as $item)
                <li>
                    <time datetime="{{ $item['datetime'] }}" class="block font-medium text-tk-ink">{{ $item['label'] }}</time>
                    {{ $item['text'] }}
                </li>
            @endforeach
        </ol>
    </aside>
</section>
