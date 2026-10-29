@php
    $tool = $weatherTool ?? null;
    $link = fn (string $routeName) => $tool ? route($routeName, $tool) : null;

    $nav = [
        [
            'section' => '1',
            'title' => 'Product Overview',
            'open' => true,
            'items' => [
                ['id' => 'about', 'num' => '1.1', 'label' => 'About this guide'],
                ['id' => 'sign-in', 'num' => '1.2', 'label' => 'Signing in to Tilkit'],
            ],
        ],
        [
            'section' => '2',
            'title' => 'Weather log',
            'open' => true,
            'items' => [
                ['id' => 'navigation', 'num' => '2.1', 'label' => 'Navigation'],
                ['id' => 'setup', 'num' => '2.2', 'label' => 'Setup'],
                ['id' => 'capture', 'num' => '2.3', 'label' => 'Capture'],
                ['id' => 'wizard', 'num' => '2.4', 'label' => 'Workshop wizard'],
                ['id' => 'sighting', 'num' => '2.5', 'label' => 'Field sightings'],
                ['id' => 'records', 'num' => '2.6', 'label' => 'Records'],
            ],
        ],
        [
            'section' => '3',
            'title' => 'Quality and publish',
            'open' => true,
            'items' => [
                ['id' => 'validation', 'num' => '3.1', 'label' => 'Validation and data desk'],
                ['id' => 'tally', 'num' => '3.2', 'label' => 'Seasonal tally'],
                ['id' => 'analysis', 'num' => '3.3', 'label' => 'Analysis'],
                ['id' => 'exports', 'num' => '3.4', 'label' => 'Exports and consent'],
            ],
        ],
        [
            'section' => '4',
            'title' => 'Administration',
            'open' => false,
            'items' => [
                ['id' => 'team', 'num' => '4.1', 'label' => 'Team and roles'],
                ['id' => 'cycle', 'num' => '4.2', 'label' => 'Seasonal cycle'],
            ],
        ],
    ];

    $openState = collect($nav)->mapWithKeys(fn ($g) => ['s'.$g['section'] => $g['open']])->all();

    $subnavTiles = [
        ['Home', 'Summary, map, quick links'],
        ['Capture', 'New indicator record'],
        ['Records', 'Search and edit rows'],
        ['Analysis', 'Charts and breakdowns'],
        ['Tally', 'Tercile engine'],
        ['Data desk', 'Review queues'],
        ['Setup', 'Programs, sites, seasons'],
        ['Team', 'Invites and roles'],
    ];
@endphp

<article
    class="tk-guide-page"
    x-data="{
        search: '',
        active: 'about',
        open: @js($openState),
        init() {
            const sections = document.querySelectorAll('.tk-guide-section-item');
            if (! sections.length) return;
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        this.active = entry.target.id;
                    }
                });
            }, { rootMargin: '-20% 0px -65% 0px', threshold: 0 });
            sections.forEach((el) => observer.observe(el));
        },
        toggle(key) {
            this.open[key] = ! this.open[key];
        },
        itemVisible(label) {
            const q = this.search.trim().toLowerCase();
            if (! q) return true;
            return label.toLowerCase().includes(q);
        },
        groupVisible(group) {
            const q = this.search.trim().toLowerCase();
            if (! q) return true;
            const head = (`section ${group.section} ${group.title}`).toLowerCase();
            if (head.includes(q)) return true;
            return group.items.some((item) => this.itemVisible(`${item.label} ${item.num}`));
        },
        sectionVisible(text) {
            const q = this.search.trim().toLowerCase();
            if (! q) return true;
            return text.toLowerCase().includes(q);
        },
    }"
>
    @guest
        <x-page-header
            eyebrow="Documentation"
            title="User guide"
            subtitle="TILKIT Weather Indicator Log on Tilkit - setup, capture, validation, seasonal tercile tally, and governed exports."
            class="mb-8 sm:mb-10"
        >
            <x-slot name="actions">
                <a href="{{ route('register') }}" class="tk-btn-primary">Create account</a>
                <a href="{{ route('login') }}" class="tk-btn-secondary">Sign in</a>
            </x-slot>
        </x-page-header>
    @endguest

    <div class="tk-guide-layout">
        <aside class="tk-guide-sidebar" aria-label="Guide contents">
            <div class="tk-guide-sidebar-panel">
                <p class="text-xs font-medium uppercase tracking-tk-label text-tk-accent px-1 pb-2">On this page</p>

                <label class="tk-guide-sidebar-search">
                    <span class="sr-only">Search in user guide</span>
                    <svg class="tk-guide-search-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                    <input type="search" x-model="search" placeholder="Search topics" autocomplete="off" />
                </label>

                <nav>
                    @include('user-guide.nav', ['nav' => $nav])
                </nav>
            </div>
        </aside>

        <div class="min-w-0">
            @auth
                @if ($link('tools.tilkit.records.create'))
                    <div class="flex flex-wrap gap-3 mb-8 lg:hidden">
                        <a href="{{ $link('tools.tilkit.records.create') }}" class="tk-btn-secondary">Capture</a>
                    </div>
                @endif
            @endauth

            <div class="space-y-10">
            <section class="tk-guide-chapter" aria-labelledby="guide-chapter-1">
                <h2 id="guide-chapter-1" class="tk-guide-chapter-title"><span class="text-tk-accent">Section 1</span> Product overview</h2>

                <x-guide.section
                    id="about"
                    num="1.1"
                    title="About this guide"
                    search="about tilkit weather log workbook v4 indicator community data list tercile"
                    lead="TILKIT Weather Log on Tilkit is an interactive cloud platform for community weather indicators: one row per sign, site, and season, with validation, tally, and governed exports."
                >
                    <p>Each indicator record is a single workbook row: one community sign at one site for one monitoring season. You enter raw fields in the app; the rules engine calculates derived columns including eligibility, weights, tally status, and integration fields.</p>
                    <p>Definitions come from the v4 register and Community Data List. The same gate rules as Excel apply before a row counts toward the tercile tally.</p>
                    <p class="text-sm text-tk-muted">90 raw columns · 34 derived · Community Data List sections A-E</p>
                    @if ($link('tools.tilkit.workbook'))
                        <x-guide.link href="{{ $link('tools.tilkit.workbook') }}">Workbook guides in the app</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section
                    id="sign-in"
                    num="1.2"
                    title="Signing in to Tilkit"
                    search="sign in login register account password email"
                >
                    <p>Create an account from the registration page, then sign in with your email and password. After sign-in, open <strong>Weather log</strong> from the main navigation to reach your program workspace.</p>
                    <p>Invited team members use the same sign-in flow; administrators assign roles and export permissions under Team.</p>
                </x-guide.section>
            </section>

            <section class="tk-guide-chapter" aria-labelledby="guide-chapter-2">
                <h2 id="guide-chapter-2" class="tk-guide-chapter-title"><span class="text-tk-accent">Section 2</span> Weather log</h2>

                <x-guide.section
                    id="navigation"
                    num="2.1"
                    title="Navigation"
                    search="navigation sub-nav home capture records analysis tally data desk setup team"
                    lead="After sign-in, open Weather log. The sub-navigation follows how communities work through a monitoring year."
                >
                    <div class="tk-guide-feature-grid">
                        @foreach ($subnavTiles as [$label, $desc])
                            <div class="tk-guide-feature">
                                <p class="tk-guide-feature-title">{{ $label }}</p>
                                <p class="tk-guide-feature-desc">{{ $desc }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p>Switch programs under the sub-nav when you work across initiatives. Sites and seasons always belong to the active program.</p>
                </x-guide.section>

                <x-guide.section
                    id="setup"
                    num="2.2"
                    title="Setup"
                    search="setup program sites seasons code legend import"
                    lead="Configure programs, sites, and seasons before field work begins."
                >
                    <ul class="tk-guide-list">
                        <li><strong>Program</strong> - group sites and seasons (Setup wizard: Program, Seasons, Sites).</li>
                        <li><strong>Sites (AI)</strong> - community or station, geography, optional map centre for GPS.</li>
                        <li><strong>Seasons (AM)</strong> - named month ranges, including year wrap.</li>
                        <li><strong>Season settings</strong> - weighting, priors, integration hooks.</li>
                        <li><strong>Code legend</strong> - <span class="tk-guide-code">tilkit:import-legend</span> for official indicator UIDs.</li>
                    </ul>
                    @if ($link('tools.tilkit.setup'))
                        <x-guide.link href="{{ $link('tools.tilkit.setup') }}">Open Setup</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section
                    id="capture"
                    num="2.3"
                    title="Capture"
                    search="capture indicator record sector impacts photos derived"
                    lead="From Capture, choose program, site, season, and indicator, then work through staged field groups labeled A through DT."
                >
                    <ul class="tk-guide-list">
                        <li><strong>Sector impacts (I)</strong> and <strong>ratings (X)</strong> - one item per field; add or remove rows as needed.</li>
                        <li><strong>Photos (Y)</strong> - at least three recommended.</li>
                        <li>Derived values stay read-only; the engine refreshes them on every save.</li>
                    </ul>
                    @if ($link('tools.tilkit.records.create'))
                        <x-guide.link href="{{ $link('tools.tilkit.records.create') }}">Start capture</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section
                    id="wizard"
                    num="2.4"
                    title="Workshop wizard"
                    search="wizard workshop community data list sections A B C D E"
                    lead="The wizard follows the Community Data List used in FGD and plenary. It is designed for conversation, not spreadsheet grid entry."
                >
                    <div class="tk-guide-feature-grid">
                        <div class="tk-guide-feature">
                            <p class="tk-guide-feature-title">Section A</p>
                            <p class="tk-guide-feature-desc">For every sign: names, N, P-U, M/V, AC-AD, AP, photos, GPS, consent.</p>
                        </div>
                        <div class="tk-guide-feature">
                            <p class="tk-guide-feature-title">Sections B-E</p>
                            <p class="tk-guide-feature-desc">Integration, calendar flags, anticipatory action, communication, official outlook.</p>
                        </div>
                    </div>
                </x-guide.section>

                <x-guide.section id="sighting" num="2.5" title="Field sightings" search="sighting observer events photos evidence">
                    <p>Log observer events on an existing record without creating a duplicate row. One indicator, site, and season per row; sightings attach as children. Update N, O, and evidence throughout the season.</p>
                </x-guide.section>

                <x-guide.section id="records" num="2.6" title="Records" search="records browse filter edit search site season">
                    <p>Filter by search, site, and season with live updates. Open any row to edit fields, run the wizard, or add sightings. Columns AH-AM sync from your catalogue and setup.</p>
                    @if ($link('tools.tilkit.records.index'))
                        <x-guide.link href="{{ $link('tools.tilkit.records.index') }}">Browse records</x-guide.link>
                    @endif
                </x-guide.section>
            </section>

            <section class="tk-guide-chapter" aria-labelledby="guide-chapter-3">
                <h2 id="guide-chapter-3" class="tk-guide-chapter-title"><span class="text-tk-accent">Section 3</span> Quality and publish</h2>

                <x-guide.section
                    id="validation"
                    num="3.1"
                    title="Validation and data desk"
                    search="validation confirm approve verify contested data desk AC AD AE AP"
                    lead="Quality is a chain: confirm, approve, verify, and resolve contested or climate-flagged rows before tally."
                >
                    <div class="tk-guide-feature-grid">
                        <div class="tk-guide-feature"><span class="tk-guide-code">AC</span> Confirmed</div>
                        <div class="tk-guide-feature"><span class="tk-guide-code">AD</span> Approved</div>
                        <div class="tk-guide-feature"><span class="tk-guide-code">AE</span> Verified</div>
                        <div class="tk-guide-feature"><span class="tk-guide-code">AP</span> Not contested</div>
                    </div>
                    @if ($link('tools.tilkit.data-desk'))
                        <x-guide.link href="{{ $link('tools.tilkit.data-desk') }}">Open data desk</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section id="tally" num="3.2" title="Seasonal tally" search="tally tercile engine publish snapshot CE weighting">
                    <p>Run the v4 engine for a site and season: gate CE, weighting, tercile counts, onset and cessation summaries, reconciliation. Publish snapshots when your process allows.</p>
                    @if ($link('tools.tilkit.tally'))
                        <x-guide.link href="{{ $link('tools.tilkit.tally') }}">Open tally</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section id="analysis" num="3.3" title="Analysis" search="analysis insights charts maps monitoring">
                    <p>Monitor progress with volumes by site and season, validation breakdowns, and maps. Use alongside the formal tally for M&amp;E.</p>
                    @if ($link('tools.tilkit.insights'))
                        <x-guide.link href="{{ $link('tools.tilkit.insights') }}">Open analysis</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section id="exports" num="3.4" title="Exports and consent" search="export csv json xlsx consent BC do no harm">
                    <p>CSV, JSON, and XLSX respect consent (BC) and do-no-harm rules. Export access is controlled per team member. Column order matches A-DT for template parity.</p>
                </x-guide.section>
            </section>

            <section class="tk-guide-chapter" aria-labelledby="guide-chapter-4">
                <h2 id="guide-chapter-4" class="tk-guide-chapter-title"><span class="text-tk-accent">Section 4</span> Administration</h2>

                <x-guide.section id="team" num="4.1" title="Team and roles" search="team invite roles administrator collector viewer export">
                    <p>Invite colleagues by email. Roles include field collector, viewer, and administrator. Toggle export permission where needed. Personas can further guard data desk and publish routes.</p>
                    @if ($link('tools.tilkit.team'))
                        <x-guide.link href="{{ $link('tools.tilkit.team') }}">Manage team</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section id="cycle" num="4.2" title="Seasonal cycle" search="pre-season workshop integration disseminate outcomes">
                    <ul class="tk-guide-list">
                        <li><strong>Pre-season</strong> - settings, training, facilitator prep.</li>
                        <li><strong>Workshop</strong> - section A, tercile votes.</li>
                        <li><strong>During season</strong> - sightings, photos, panels.</li>
                        <li><strong>Integration</strong> - community and official outlook.</li>
                        <li><strong>Tally and disseminate</strong> - engine, publish, export.</li>
                        <li><strong>After season</strong> - outcomes and history for weighting.</li>
                    </ul>
                </x-guide.section>
            </section>

            <footer class="tk-guide-footer">
                <p>
                    Workbook guides in the app mirror all 12 master-template sheets and the Community Data List capture flow.
                </p>
            </footer>
            </div>
        </div>
    </div>
</article>
