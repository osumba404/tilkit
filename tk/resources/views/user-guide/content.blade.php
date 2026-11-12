@php
    $tool = $weatherTool ?? null;
    $link = fn (string $routeName) => $tool ? route($routeName, $tool) : null;

    $nav = [
        [
            'section' => '1',
            'title' => 'Start here',
            'open' => true,
            'items' => [
                ['id' => 'about', 'num' => '1.1', 'label' => 'What is this app?'],
                ['id' => 'getting-started', 'num' => '1.2', 'label' => 'Getting started'],
                ['id' => 'sign-in', 'num' => '1.3', 'label' => 'Accounts and sign-in'],
            ],
        ],
        [
            'section' => '2',
            'title' => 'Using the weather log',
            'open' => true,
            'items' => [
                ['id' => 'navigation', 'num' => '2.1', 'label' => 'Finding your way around'],
                ['id' => 'setup', 'num' => '2.2', 'label' => 'Setting up your programme'],
                ['id' => 'capture', 'num' => '2.3', 'label' => 'Recording signs'],
                ['id' => 'wizard', 'num' => '2.4', 'label' => 'Workshop steps'],
                ['id' => 'sighting', 'num' => '2.5', 'label' => 'Extra observations'],
                ['id' => 'records', 'num' => '2.6', 'label' => 'Finding past records'],
            ],
        ],
        [
            'section' => '3',
            'title' => 'Checks and sharing',
            'open' => true,
            'items' => [
                ['id' => 'validation', 'num' => '3.1', 'label' => 'Checking the data'],
                ['id' => 'tally', 'num' => '3.2', 'label' => 'Season summary'],
                ['id' => 'analysis', 'num' => '3.3', 'label' => 'Charts and maps'],
                ['id' => 'exports', 'num' => '3.4', 'label' => 'Downloads and privacy'],
            ],
        ],
        [
            'section' => '4',
            'title' => 'For organisers',
            'open' => false,
            'items' => [
                ['id' => 'team', 'num' => '4.1', 'label' => 'Team and permissions'],
                ['id' => 'cycle', 'num' => '4.2', 'label' => 'A year in the community'],
            ],
        ],
    ];

    $openState = collect($nav)->mapWithKeys(fn ($g) => ['s'.$g['section'] => $g['open']])->all();

    $subnavTiles = [
        ['Home', 'Summary and quick links'],
        ['Capture', 'Add or update a sign'],
        ['Records', 'Search what you already entered'],
        ['Analysis', 'Charts and progress'],
        ['Tally', 'Season forecast summary'],
        ['Data desk', 'Rows that need attention'],
        ['Setup', 'Programme, places, and seasons'],
        ['Team', 'Invite colleagues'],
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
            eyebrow="Help"
            title="User guide"
            title-id="user-guide-title"
            subtitle="Community weather signs on Tilkit - explained in everyday language."
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
                        <a href="{{ $link('tools.tilkit.records.create') }}" class="tk-btn-secondary">Start recording</a>
                    </div>
                @endif
            @endauth

            <div class="space-y-10">
            <section class="tk-guide-chapter" aria-labelledby="guide-chapter-1">
                <h2 id="guide-chapter-1" class="tk-guide-chapter-title"><span class="text-tk-accent">Section 1</span> Start here</h2>

                <x-guide.section
                    id="about"
                    num="1.1"
                    title="What is this app?"
                    search="about tilkit weather signs community rain season forecast"
                    lead="Tilkit helps communities record local weather signs - plants, animals, sky, water, rituals, and how people feel - and turn them into a seasonal picture you can trust and share."
                >
                    <p>Each entry is <strong>one sign</strong>, tied to <strong>one place</strong> and <strong>one monitoring season</strong> (for example long rains or short rains). You type what the community agreed in the field or workshop; the app does the counting and summary rules in the background so you do not have to work like a spreadsheet.</p>
                    <p>You can use the <strong>step-by-step workshop flow</strong> for group sessions, or the main capture form when you already know what to enter. Photos and location help strengthen the story of each sign.</p>
                    <x-guide.link href="{{ route('dictionary') }}">Look up sign types (plain names and examples)</x-guide.link>
                    @if ($link('tools.tilkit.workbook'))
                        <x-guide.link href="{{ $link('tools.tilkit.workbook') }}">Full workbook reference (for facilitators)</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section
                    id="getting-started"
                    num="1.2"
                    title="Getting started"
                    search="getting started first time setup capture beginner"
                    lead="If you are new, follow this order. You can skip steps only if your programme organiser has already done them."
                >
                    <ol class="tk-guide-list list-decimal pl-5 space-y-2">
                        <li><strong>Create an account</strong> and sign in, or use an invite from your team leader.</li>
                        <li><strong>Open Weather log</strong> from the menu after sign-in.</li>
                        <li><strong>Setup</strong> - an administrator adds your programme, places (sites), and the season dates. They also check that the standard sign list appears (birds, clouds, water level, and so on). If Setup looks empty, ask them to load the official list - you do not need to fix that yourself.</li>
                        <li><strong>Capture</strong> - add each community sign once per place and season. Use the workshop steps if you are in a group session.</li>
                        <li><strong>During the season</strong> - add extra observations on the same sign without duplicating the whole row.</li>
                        <li><strong>Before you share results</strong> - responsible people confirm, approve, and check rows; then run the <strong>season summary</strong> and export only where consent allows.</li>
                    </ol>
                    @if ($link('tools.tilkit.setup'))
                        <x-guide.link href="{{ $link('tools.tilkit.setup') }}">Go to Setup</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section
                    id="sign-in"
                    num="1.3"
                    title="Accounts and sign-in"
                    search="sign in login register account password email invite admin"
                >
                    <p>Register with your email and a password, then sign in. Invited colleagues use the same sign-in page; the invite email tells them which programme they belong to.</p>
                    <p><strong>New accounts are usually “members”</strong> - they can record and view according to team rules. <strong>Administrator</strong> access (Setup, inviting others, data protection settings) is given on the <strong>Team</strong> page by someone who already manages the programme. Registering alone does not make you an administrator.</p>
                </x-guide.section>
            </section>

            <section class="tk-guide-chapter" aria-labelledby="guide-chapter-2">
                <h2 id="guide-chapter-2" class="tk-guide-chapter-title"><span class="text-tk-accent">Section 2</span> Using the weather log</h2>

                <x-guide.section
                    id="navigation"
                    num="2.1"
                    title="Finding your way around"
                    search="navigation menu home capture records analysis tally data desk setup team"
                    lead="Open Weather log after sign-in. The tabs along the top match how most programmes work through the year."
                >
                    <div class="tk-guide-feature-grid">
                        @foreach ($subnavTiles as [$label, $desc])
                            <div class="tk-guide-feature">
                                <p class="tk-guide-feature-title">{{ $label }}</p>
                                <p class="tk-guide-feature-desc">{{ $desc }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p>If you work in more than one initiative, switch <strong>programme</strong> near the top. Places and seasons always belong to the programme you have selected.</p>
                    <p class="text-sm text-tk-muted">Some programmes also show <strong>Deep Analysis</strong> (deeper charts) and an <strong>Indicator calendar</strong> (timing through the season) from Home or Analysis.</p>
                </x-guide.section>

                <x-guide.section
                    id="setup"
                    num="2.2"
                    title="Setting up your programme"
                    search="setup program sites seasons places legend catalogue mind map"
                    lead="Administrators prepare the programme before field work. Everyone else can read Setup to see places, seasons, and which signs are expected each month."
                >
                    <ul class="tk-guide-list">
                        <li><strong>Programme</strong> - name and group your places and seasons (guided steps: programme → seasons → sites).</li>
                        <li><strong>Sites (places)</strong> - community or station name, area on the map, optional map centre for GPS when recording.</li>
                        <li><strong>Seasons</strong> - label and months you monitor (including seasons that cross a year boundary).</li>
                        <li><strong>Season settings</strong> - how strongly signs count, links to official forecasts where your process uses them.</li>
                        <li><strong>Sign list</strong> - the standard catalogue of sign <em>types</em> (same idea as the public dictionary). If it is missing, your technical contact loads it once on the server; afterward it appears here for everyone.</li>
                        <li><strong>Sign map</strong> - browse types by category (plants, animals, sky, water, and so on) without entering data.</li>
                        <li><strong>Monthly sign plan per place</strong> - for each site and calendar month, tick which sign types usually appear there. Use <strong>All</strong> or <strong>Clear</strong> on a category to save time; changes save automatically.</li>
                    </ul>
                    @if ($link('tools.tilkit.setup'))
                        <x-guide.link href="{{ $link('tools.tilkit.setup') }}">Open Setup</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section
                    id="capture"
                    num="2.3"
                    title="Recording signs"
                    search="capture record new sign photos place season impacts"
                    lead="From Capture, choose programme, place, season, and sign type, then fill in what the community shared."
                >
                    <ul class="tk-guide-list">
                        <li>Pick the sign from the list that matches what people described (plants, birds, clouds, and so on).</li>
                        <li>Describe <strong>who is affected</strong> (farming, water, health, and similar) and how serious it feels.</li>
                        <li>Add <strong>photos</strong> when you can - three or more is ideal for evidence.</li>
                        <li>Some totals and status fields fill in by themselves after you save; you do not need to calculate them.</li>
                    </ul>
                    @if ($link('tools.tilkit.records.create'))
                        <x-guide.link href="{{ $link('tools.tilkit.records.create') }}">Open Capture</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section
                    id="wizard"
                    num="2.4"
                    title="Workshop steps"
                    search="wizard workshop group plenary conversation steps"
                    lead="Use this in focus groups or plenary when you want questions in the same order as the community data workshop, not a long form all at once."
                >
                    <div class="tk-guide-feature-grid">
                        <div class="tk-guide-feature">
                            <p class="tk-guide-feature-title">First block</p>
                            <p class="tk-guide-feature-desc">Names, dates, who saw the sign, confirmations, consent to share, photos, and map point.</p>
                        </div>
                        <div class="tk-guide-feature">
                            <p class="tk-guide-feature-title">Later blocks</p>
                            <p class="tk-guide-feature-desc">Linking to official forecasts, timing in the season, early action plans, how you communicate results, and the outlook you agree as a community.</p>
                        </div>
                    </div>
                    <p class="text-sm text-tk-muted">Facilitators who use the Excel workbook will see the same themes under column letters in the reference guides.</p>
                </x-guide.section>

                <x-guide.section id="sighting" num="2.5" title="Extra observations" search="sighting again observe season update photo">
                    <p>When the same sign is seen again at the same place in the same season, add an <strong>observation</strong> on the existing record instead of creating a second copy. You can update dates, notes, and photos as the season progresses.</p>
                </x-guide.section>

                <x-guide.section id="records" num="2.6" title="Finding past records" search="records search filter edit list browse">
                    <p>Filter by place and season, or search by name. Open any row to edit, open the workshop steps, or add observations. Place and programme details stay in sync with Setup.</p>
                    @if ($link('tools.tilkit.records.index'))
                        <x-guide.link href="{{ $link('tools.tilkit.records.index') }}">Browse records</x-guide.link>
                    @endif
                </x-guide.section>
            </section>

            <section class="tk-guide-chapter" aria-labelledby="guide-chapter-3">
                <h2 id="guide-chapter-3" class="tk-guide-chapter-title"><span class="text-tk-accent">Section 3</span> Checks and sharing</h2>

                <x-guide.section
                    id="validation"
                    num="3.1"
                    title="Checking the data"
                    search="validation confirm approve verify quality data desk check contested"
                    lead="Before numbers go into the season summary, agreed people check that each sign was really seen, understood, and not disputed."
                >
                    <ul class="tk-guide-list">
                        <li><strong>Confirmed</strong> - the community or observer stands by what was recorded.</li>
                        <li><strong>Approved</strong> - a lead or elder agrees it is ready to count.</li>
                        <li><strong>Verified</strong> - an independent or technical check where your process requires it.</li>
                        <li><strong>Not disputed</strong> - no open disagreement on that row.</li>
                    </ul>
                    <p>Rows that need attention (including weather disagreements) appear on the <strong>Data desk</strong> for people with that role.</p>
                    @if ($link('tools.tilkit.data-desk'))
                        <x-guide.link href="{{ $link('tools.tilkit.data-desk') }}">Open data desk</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section id="tally" num="3.2" title="Season summary" search="tally season summary forecast rains dry vote">
                    <p>When data is ready, run the <strong>season summary</strong> for a place and season. It applies the same rules as the TILKIT workbook to show how signs line up with wetter, normal, or drier expectations, plus timing themes your programme uses. Publish or share only when your governance allows.</p>
                    @if ($link('tools.tilkit.tally'))
                        <x-guide.link href="{{ $link('tools.tilkit.tally') }}">Open season summary</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section id="analysis" num="3.3" title="Charts and maps" search="analysis charts maps monitoring progress insights">
                    <p>See how much has been recorded by place and season, how far checking has progressed, and simple maps. Use this for learning and supervision alongside the formal season summary - not as a replacement for community agreement.</p>
                    @if ($link('tools.tilkit.insights'))
                        <x-guide.link href="{{ $link('tools.tilkit.insights') }}">Open Analysis</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section id="exports" num="3.4" title="Downloads and privacy" search="export download csv excel consent privacy kenya share safe">
                    <p>Download files (spreadsheet or other formats) only where <strong>consent</strong> allows and your role has export permission. The app supports do-no-harm choices and Kenya data-protection practice: who may export, what the privacy notice says, and how people can ask about their data - configured under <strong>Team</strong> by administrators.</p>
                </x-guide.section>
            </section>

            <section class="tk-guide-chapter" aria-labelledby="guide-chapter-4">
                <h2 id="guide-chapter-4" class="tk-guide-chapter-title"><span class="text-tk-accent">Section 4</span> For organisers</h2>

                <x-guide.section id="team" num="4.1" title="Team and permissions" search="team invite roles administrator collector viewer export">
                    <p>Invite people by email and choose what they can do:</p>
                    <ul class="tk-guide-list">
                        <li><strong>Administrator</strong> - Setup, team, who may export, and data protection settings.</li>
                        <li><strong>Field collector / member</strong> - record and edit signs according to programme rules (same practical access).</li>
                        <li><strong>Viewer</strong> - look without changing capture.</li>
                    </ul>
                    <p>You can also assign a <strong>workflow role</strong> (who may use the data desk, confirm rows, or publish integration results). If unsure, leave the default - it follows the tool role.</p>
                    @if ($link('tools.tilkit.team'))
                        <x-guide.link href="{{ $link('tools.tilkit.team') }}">Manage team</x-guide.link>
                    @endif
                </x-guide.section>

                <x-guide.section id="cycle" num="4.2" title="A year in the community" search="pre-season workshop season integrate share outcomes">
                    <ul class="tk-guide-list">
                        <li><strong>Before the season</strong> - settings, training, who confirms what.</li>
                        <li><strong>Workshop</strong> - agree signs and expectations together.</li>
                        <li><strong>During the season</strong> - observations, photos, community panels.</li>
                        <li><strong>Bringing views together</strong> - local outlook and links to official forecasts if you use them.</li>
                        <li><strong>End of season</strong> - season summary, agreed messages, careful exports.</li>
                        <li><strong>Afterward</strong> - keep history for the next year’s weighting and learning.</li>
                    </ul>
                </x-guide.section>
            </section>

            <footer class="tk-guide-footer">
                <p>
                    Need column-by-column detail? Use <strong>Workbook guides</strong> in the app and the <strong>indicator dictionary</strong>. This page is the everyday path for communities and facilitators.
                </p>
            </footer>
            </div>
        </div>
    </div>
</article>
