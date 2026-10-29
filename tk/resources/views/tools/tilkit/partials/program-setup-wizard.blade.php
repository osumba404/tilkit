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
            siteUpdate: @js($setupSiteUpdateUrl),
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

            <div class="tk-modal-panel animate-tk-scale-in max-w-2xl">
                <div class="shrink-0 px-6 pt-6 pb-4 border-b border-tk-line/60">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 id="tilkit-wizard-title" class="tk-section-title text-lg" x-text="modalTitle"></h2>
                            <p class="text-xs text-tk-muted mt-1" x-text="programName ? programName : 'Program → sites → seasons per site → study area map'"></p>
                        </div>
                        <button type="button" class="tk-btn-ghost text-sm px-2 py-1" @click="closeWizard()" aria-label="Close">✕</button>
                    </div>

                    <ol class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-[10px] sm:text-xs">
                        <li class="rounded-lg px-1.5 py-2 text-center border" :class="step === 1 ? 'border-tk-accent bg-tk-accent/10 font-semibold' : 'border-tk-line text-tk-muted'">1. Program</li>
                        <li class="rounded-lg px-1.5 py-2 text-center border" :class="step === 2 ? 'border-tk-accent bg-tk-accent/10 font-semibold' : 'border-tk-line text-tk-muted'">2. Sites</li>
                        <li class="rounded-lg px-1.5 py-2 text-center border" :class="step === 3 ? 'border-tk-accent bg-tk-accent/10 font-semibold' : 'border-tk-line text-tk-muted'">3. Seasons</li>
                        <li class="rounded-lg px-1.5 py-2 text-center border" :class="step === 4 ? 'border-tk-accent bg-tk-accent/10 font-semibold' : 'border-tk-line text-tk-muted'">4. Study map</li>
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
                                <span x-show="!busy">Next · Sites</span>
                                <span x-show="busy">Saving…</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="step === 2" class="space-y-3 pt-1">
                        <p class="text-sm text-tk-muted">Add monitoring sites (study areas). Each site gets its own season calendar and map in the next steps.</p>

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
                            <button type="button" class="tk-btn-secondary" @click="step = 1">Back</button>
                            <button type="button" class="tk-btn-primary" :disabled="busy || sites.length === 0" @click="goToSeasonsStep()">
                                Next · Seasons
                            </button>
                        </div>
                    </div>

                    <div x-show="step === 3" class="space-y-4 pt-1">
                        <div>
                            <label class="text-xs text-tk-muted block mb-1">Site (study area)</label>
                            <select x-model.number="activeSiteId" @change="onActiveSiteChange()" class="tk-input">
                                <template x-for="site in sites" :key="'sel-' + site.id">
                                    <option :value="site.id" x-text="siteLabelForSeasonStep(site)"></option>
                                </template>
                            </select>
                        </div>

                        <p class="text-sm text-tk-muted">
                            Define seasons for <strong x-text="activeSite?.name || 'this site'"></strong>. Each season has a start and end month; together they should cover all twelve months in order.
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

                        <ul class="text-sm space-y-1" x-show="activeSeasons.length > 0">
                            <template x-for="s in activeSeasons" :key="s.id">
                                <li class="flex justify-between border-b border-tk-line/60 py-1">
                                    <span x-text="s.label"></span>
                                    <span class="text-tk-muted text-xs" x-text="'Months ' + s.start_month + '–' + s.end_month"></span>
                                </li>
                            </template>
                        </ul>

                        <div x-show="!activeMonthsComplete" class="space-y-2 border-t border-tk-line pt-3">
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
                                <p class="text-xs text-tk-muted mt-1">Suggested from months: <span x-text="suggestedSeasonName || '-'"></span></p>
                            </div>
                            <button type="button" class="tk-btn-secondary w-full" :disabled="busy" @click="addSeason()">
                                Add this season
                            </button>
                        </div>

                        <p x-show="activeMonthsComplete" class="text-sm text-tk-accent font-medium">All twelve months are assigned for this site.</p>
                        <p x-show="allSitesSeasonsComplete()" class="text-sm text-tk-accent font-medium">Every site has a full season calendar.</p>

                        <div class="flex justify-between gap-2 pt-2">
                            <button type="button" class="tk-btn-secondary" @click="step = 2">Back</button>
                            <button type="button" class="tk-btn-primary" :disabled="!allSitesSeasonsComplete() || busy" @click="goToMapStep()">
                                Next · Study map
                            </button>
                        </div>
                    </div>

                    <div x-show="step === 4" class="space-y-3 pt-1">
                        <p class="text-sm text-tk-muted">
                            Choose a site, then outline its study area (3+ corners) or place a center pin.
                            Saved study areas show as an outline and a <strong>centre marker</strong> only (no corner pins until you choose <strong>Edit shape</strong> in the popup).
                            While drawing, click a corner pin to remove it, or use Undo / Clear.
                            <strong>Hover</strong> a centre marker for info; <strong>click</strong> it for actions (edit, delete, save, centre map).
                        </p>

                        <div
                            class="tk-gps-fullscreen-host tk-wizard-study-map-host rounded-tk border border-tk-line overflow-hidden bg-tk-soft"
                            x-ref="studyMapHost"
                            :class="studyMapFullscreenFallback ? 'tk-gps-fullscreen-fallback' : ''"
                        >
                            <div class="tk-wizard-map-toolbar p-2 sm:p-3 border-b border-tk-line/80 bg-tk-surface/95 flex flex-wrap items-end gap-2">
                                <div class="min-w-[12rem] flex-1">
                                    <label class="text-xs text-tk-muted block mb-1">Site / study area</label>
                                    <select x-model.number="mapSiteId" @change="onMapSiteChange()" class="tk-input text-sm w-full">
                                        <template x-for="site in sites" :key="'map-site-' + site.id">
                                            <option :value="site.id" x-text="siteLabelForMapStep(site)"></option>
                                        </template>
                                    </select>
                                </div>
                                <button type="button" class="tk-btn-secondary text-xs py-2 shrink-0" @click="studyMapToggleFullscreen()">
                                    <span x-text="studyMapIsFullscreen ? 'Exit full screen' : 'Full screen'"></span>
                                </button>
                            </div>
                            <div class="relative tk-gps-fullscreen-map-shell bg-tk-soft">
                                <div class="h-56 w-full tk-wizard-map-canvas" x-ref="studyMapCanvas" data-tk-wizard-study-map></div>
                            </div>
                            <div class="tk-wizard-map-actions shrink-0 p-2 sm:p-3 border-t border-tk-line/80 bg-tk-surface/95 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" class="tk-btn-secondary text-xs py-2" :disabled="isStudyShapeLocked()" @click="studyMapLocate()">Use my location</button>
                                    <button type="button" class="tk-btn-secondary text-xs py-2" :disabled="isStudyShapeLocked()" @click="studyMapUndo()">Undo corner</button>
                                    <button type="button" class="tk-btn-secondary text-xs py-2" :disabled="isStudyShapeLocked()" @click="studyMapClear()">Clear</button>
                                    <button type="button" class="tk-btn-primary text-xs py-2 sm:ml-auto" :disabled="busy || isStudyShapeLocked()" @click="saveStudyAreaStep(false)">Save study area</button>
                                </div>
                                <p class="text-xs text-tk-muted" x-text="studyMapStatus"></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-between gap-2 pt-2 border-t border-tk-line">
                            <button type="button" class="tk-btn-secondary" @click="step = 3">Back</button>
                            <button type="button" class="tk-btn-primary" :disabled="busy" @click="saveStudyAreaStep(true)">Finish</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
