<div
    data-tilkit-program-wizard
    x-data="tilkitProgramWizard({
        csrf: @js(csrf_token()),
        monthLabels: @js($seasonMonths),
        monthShort: @js($seasonMonthShort),
        urls: {
            program: @js($setupProgramUrl),
            programUpdate: @js($setupProgramUpdateUrl),
            season: @js($setupSeasonUrl),
            site: @js($setupSiteUrl),
            continents: @js($geographyContinentsUrl),
            countries: @js($geographyCountriesUrl),
        },
    })"
    @keydown.escape.window="if (open) closeWizard()"
>
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="tk-modal-overlay"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tilkit-wizard-title"
        >
            <div class="tk-modal-backdrop" @click="closeWizard()" aria-hidden="true"></div>

            <div class="tk-modal-panel animate-tk-scale-in">
                <div class="shrink-0 px-6 pt-6 pb-4 border-b border-tk-line/60">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 id="tilkit-wizard-title" class="tk-section-title text-lg" x-text="modalTitle"></h2>
                            <p class="text-xs text-tk-muted mt-1" x-text="programName ? programName : 'Program → seasons → sites'"></p>
                        </div>
                        <button type="button" class="tk-btn-ghost text-sm px-2 py-1" @click="closeWizard()" aria-label="Close">✕</button>
                    </div>

                    <ol class="flex gap-2 text-xs">
                        <li class="flex-1 rounded-lg px-2 py-2 text-center border" :class="step === 1 ? 'border-tk-accent bg-tk-accent/10 font-semibold' : 'border-tk-line text-tk-muted'">1. Program</li>
                        <li class="flex-1 rounded-lg px-2 py-2 text-center border" :class="step === 2 ? 'border-tk-accent bg-tk-accent/10 font-semibold' : 'border-tk-line text-tk-muted'">2. Seasons</li>
                        <li class="flex-1 rounded-lg px-2 py-2 text-center border" :class="step === 3 ? 'border-tk-accent bg-tk-accent/10 font-semibold' : 'border-tk-line text-tk-muted'">3. Sites</li>
                    </ol>
                </div>

                <div class="tk-modal-panel-body">
                    <div x-show="error" class="tk-error-banner mb-3 mt-4 text-sm" role="alert">
                        <p class="font-medium">Could not save</p>
                        <p class="mt-1" x-text="error"></p>
                    </div>

                    <div x-show="step === 1" class="space-y-3 pt-1">
                        <div>
                            <label class="text-xs text-tk-muted block mb-1">Program / initiative name</label>
                            <input type="text" x-model="programName" class="tk-input" maxlength="160" placeholder="e.g. Ghana pilot 2019–2026" required>
                        </div>
                        <div>
                            <label class="text-xs text-tk-muted block mb-1">Note (optional)</label>
                            <textarea x-model="programDescription" rows="2" class="tk-input" placeholder="Years, funders, geography…"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="tk-btn-secondary" @click="closeWizard()">Cancel</button>
                            <button type="button" class="tk-btn-primary" :disabled="busy" @click="submitProgramStep()">
                                <span x-show="!busy">Next · Seasons</span>
                                <span x-show="busy">Saving…</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="step === 2" class="space-y-4 pt-1">
                        <p class="text-sm text-tk-muted">
                            Define each season with a <strong>start</strong> and <strong>end</strong> month. Your first season can start in any month (default January). Each later season starts the month after the previous one ends, until all twelve months are covered (December can wrap to January).
                        </p>

                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-1.5">
                            <template x-for="m in monthGrid" :key="m.num">
                                <div
                                    class="text-center text-[10px] sm:text-xs rounded-md py-1.5 px-0.5 border"
                                    :class="m.covered ? 'bg-tk-accent/15 border-tk-accent text-tk-ink' : 'bg-tk-soft border-tk-line text-tk-muted'"
                                    x-text="m.label.slice(0, 3)"
                                ></div>
                            </template>
                        </div>

                        <ul class="text-sm space-y-1" x-show="seasons.length > 0">
                            <template x-for="s in seasons" :key="s.id">
                                <li class="flex justify-between border-b border-tk-line/60 py-1">
                                    <span x-text="s.label"></span>
                                    <span class="text-tk-muted text-xs" x-text="'Months ' + s.start_month + '–' + s.end_month"></span>
                                </li>
                            </template>
                        </ul>

                        <div x-show="!monthsComplete" class="space-y-2 border-t border-tk-line pt-3">
                            <p class="text-xs text-tk-muted" x-show="!isFirstSeason">
                                This season starts in
                                <strong x-text="monthLabels[activeStartMonth] || activeStartMonth"></strong>
                                (month after the previous season).
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-tk-muted block mb-1">Start month</label>
                                    <select
                                        x-model.number="seasonStartMonth"
                                        @change="syncSeasonState()"
                                        class="tk-input"
                                        :disabled="!isFirstSeason"
                                    >
                                        <template x-for="m in startMonthOptions" :key="'start-' + m.num">
                                            <option :value="m.num" x-text="m.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-tk-muted block mb-1">End month</label>
                                    <select x-model.number="seasonEndMonth" @change="syncSeasonNameSuggestion()" class="tk-input">
                                        <template x-for="m in endMonthOptions" :key="'end-' + m.num">
                                            <option :value="m.num" x-text="m.label"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-tk-muted block mb-1">Season name</label>
                                <input type="text" x-model="seasonName" class="tk-input" maxlength="80" placeholder="e.g. MAM 2025, Long rains, OND">
                                <p class="text-xs text-tk-muted mt-1">Suggested from months: <span x-text="suggestedSeasonName || '—'"></span></p>
                            </div>
                            <button type="button" class="tk-btn-secondary w-full" :disabled="busy" @click="addSeason()">
                                Add this season
                            </button>
                        </div>

                        <p x-show="monthsComplete" class="text-sm text-tk-accent font-medium">All twelve months are assigned.</p>

                        <div class="flex justify-between gap-2 pt-2">
                            <button type="button" class="tk-btn-secondary" @click="step = 1">Back</button>
                            <button type="button" class="tk-btn-primary" :disabled="!monthsComplete || busy" @click="goToSitesStep()">
                                Next · Sites
                            </button>
                        </div>
                    </div>

                    <div x-show="step === 3" class="space-y-3 pt-1">
                        <p class="text-sm text-tk-muted">Add one or more monitoring sites for this program.</p>

                        <ul class="text-sm space-y-1 max-h-32 overflow-y-auto overscroll-contain" x-show="sites.length > 0">
                            <template x-for="site in sites" :key="site.id">
                                <li class="border-b border-tk-line/60 py-1" x-text="site.name"></li>
                            </template>
                        </ul>

                        <div class="grid sm:grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-tk-muted block mb-1">Continent</label>
                                <select x-model="siteDraft.continent" @change="loadCountries()" class="tk-input">
                                    <option value="">Select continent</option>
                                    <template x-for="row in continents" :key="row.name">
                                        <option :value="row.name" x-text="row.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-tk-muted block mb-1">Country</label>
                                <select x-model="siteDraft.country" class="tk-input" :disabled="countries.length === 0">
                                    <option value="">Select country</option>
                                    <template x-for="row in countries" :key="row.code">
                                        <option :value="row.code" x-text="row.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <input type="text" x-model="siteDraft.county" class="tk-input" placeholder="County / district / region">
                        <input type="text" x-model="siteDraft.ward" class="tk-input" placeholder="Ward / locality (optional)">
                        <input type="text" x-model="siteDraft.name" class="tk-input" placeholder="Site name (required)">
                        <input type="text" x-model="siteDraft.primary_language" class="tk-input" placeholder="Primary language (optional)">

                        <button type="button" class="tk-btn-secondary w-full" :disabled="busy" @click="addSite()">
                            Add site
                        </button>

                        <div class="flex justify-between gap-2 pt-2 border-t border-tk-line">
                            <button type="button" class="tk-btn-secondary" @click="step = 2">Back</button>
                            <button type="button" class="tk-btn-primary" :disabled="busy || sites.length === 0" @click="finishWizard()">
                                Finish
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
