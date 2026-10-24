import { messageFromApiResponse } from './json-api-error';

export function registerTilkitProgramWizard() {
    Alpine.data('tilkitProgramWizard', (config) => ({
        open: false,
        mode: 'create',
        step: 1,
        busy: false,
        error: '',
        programId: null,
        programName: '',
        programDescription: '',
        seasons: [],
        monthsComplete: false,
        seasonStartMonth: 1,
        seasonEndMonth: 1,
        seasonName: '',
        monthShort: config.monthShort || {},
        sites: [],
        continents: [],
        countries: [],
        siteDraft: {
            continent: '',
            country: '',
            county: '',
            ward: '',
            name: '',
            primary_language: '',
        },
        monthLabels: config.monthLabels || {},
        csrf: config.csrf,
        urls: config.urls,

        init() {
            document.querySelectorAll('[data-tilkit-wizard-open]').forEach((el) => {
                el.addEventListener('click', () => {
                    const trigger = el.dataset.tilkitWizardOpen;
                    if (trigger === 'create') {
                        this.openWizardCreate();
                    } else if (trigger === 'edit' && el.dataset.program) {
                        try {
                            this.openWizardEdit(JSON.parse(el.dataset.program));
                        } catch {
                            this.error = 'Could not open this program.';
                        }
                    }
                });
            });
        },

        get modalTitle() {
            return this.mode === 'edit' ? 'Edit program setup' : 'New program setup';
        },

        get monthGrid() {
            const covered = this.coverageMap();
            return Array.from({ length: 12 }, (_, i) => {
                const num = i + 1;
                return {
                    num,
                    label: this.monthLabels[num] || String(num),
                    covered: covered[num] || false,
                };
            });
        },

        get isFirstSeason() {
            return this.seasons.length === 0;
        },

        get chainedStartMonth() {
            if (this.seasons.length === 0) {
                return null;
            }
            const last = this.seasons[this.seasons.length - 1];

            return this.monthAfter(last.end_month);
        },

        get activeStartMonth() {
            if (this.isFirstSeason) {
                return parseInt(this.seasonStartMonth, 10) || 1;
            }

            return this.chainedStartMonth;
        },

        get startMonthOptions() {
            return this.monthGrid;
        },

        get endMonthOptions() {
            const start = this.activeStartMonth;
            if (!start) {
                return [];
            }
            const covered = this.coverageMap();
            const options = [];
            let month = start;
            for (let i = 0; i < 12; i++) {
                if (covered[month]) {
                    break;
                }
                options.push({
                    num: month,
                    label: this.monthLabels[month] || String(month),
                });
                if (month === start && i > 0) {
                    break;
                }
                month = this.monthAfter(month);
            }

            return options;
        },

        get suggestedSeasonName() {
            const start = this.activeStartMonth;
            const end = parseInt(this.seasonEndMonth, 10);
            const months = this.monthsInSeason(start, end);
            if (months.length === 0) {
                return '';
            }
            const short = this.monthShort;
            const startLabel = short[start] || String(start);
            const endLabel = short[end] || String(end);

            return start === end ? startLabel : `${startLabel}–${endLabel}`;
        },

        monthAfter(month) {
            const m = parseInt(month, 10);

            return m >= 12 ? 1 : m + 1;
        },

        monthsInSeason(start, end) {
            const s = parseInt(start, 10);
            const e = parseInt(end, 10);
            if (!s || !e) {
                return [];
            }
            const months = [];
            let month = s;
            for (let i = 0; i < 12; i++) {
                months.push(month);
                if (month === e) {
                    return months;
                }
                month = this.monthAfter(month);
            }

            return [];
        },

        coverageMap() {
            const map = {};
            for (let m = 1; m <= 12; m++) {
                map[m] = false;
            }
            this.seasons.forEach((season) => {
                this.monthsInSeason(season.start_month, season.end_month).forEach((m) => {
                    map[m] = true;
                });
            });

            return map;
        },

        syncSeasonState() {
            const map = this.coverageMap();
            this.monthsComplete = Object.keys(map).every((k) => map[k]);
            if (this.monthsComplete) {
                return;
            }
            if (!this.isFirstSeason) {
                this.seasonStartMonth = this.chainedStartMonth;
            }
            const start = this.activeStartMonth;
            const validEnds = this.endMonthOptions.map((o) => o.num);
            if (!validEnds.includes(parseInt(this.seasonEndMonth, 10))) {
                this.seasonEndMonth = start;
            }
            this.syncSeasonNameSuggestion();
        },

        syncSeasonNameSuggestion() {
            const suggested = this.suggestedSeasonName;
            if (!this.seasonName.trim() || this.seasonName === this._lastSuggestedSeasonName) {
                this.seasonName = suggested;
            }
            this._lastSuggestedSeasonName = suggested;
        },

        programUpdateUrl() {
            return this.urls.programUpdate.replace(/\/programs\/0(\/|$|\?)/, `/programs/${this.programId}$1`);
        },

        setPageScrollLocked(locked) {
            document.documentElement.classList.toggle('tk-modal-open', locked);
        },

        openWizardCreate() {
            this.resetWizard();
            this.mode = 'create';
            this.open = true;
            this.setPageScrollLocked(true);
            this.loadContinents();
        },

        openWizardEdit(payload) {
            this.resetWizard();
            this.mode = 'edit';
            this.programId = payload.id;
            this.programName = payload.name || '';
            this.programDescription = payload.description || '';
            this.seasons = (payload.seasons || []).map((s) => ({
                id: s.id,
                label: s.label,
                start_month: s.start_month,
                end_month: s.end_month,
            }));
            this.sites = (payload.sites || []).map((site) => ({
                id: site.id,
                name: site.name,
            }));
            this.syncSeasonState();
            this.step = 1;
            this.open = true;
            this.setPageScrollLocked(true);
            this.loadContinents();
            this.$nextTick(() => this.syncSeasonNameSuggestion());
        },

        closeWizard() {
            if (this.busy) {
                return;
            }
            this.open = false;
            this.setPageScrollLocked(false);
        },

        resetWizard() {
            this.step = 1;
            this.error = '';
            this.mode = 'create';
            this.programId = null;
            this.programName = '';
            this.programDescription = '';
            this.seasons = [];
            this.monthsComplete = false;
            this.seasonStartMonth = 1;
            this.seasonEndMonth = 1;
            this.seasonName = '';
            this.sites = [];
            this.siteDraft = {
                continent: '',
                country: '',
                county: '',
                ward: '',
                name: '',
                primary_language: '',
            };
            this.countries = [];
        },

        async requestJson(url, method, body) {
            const res = await fetch(url, {
                method,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(messageFromApiResponse(json, 'We could not save that step. Please check the form and try again.'));
            }
            return json;
        },

        async postJson(url, body) {
            return this.requestJson(url, 'POST', body);
        },

        async patchJson(url, body) {
            return this.requestJson(url, 'PATCH', body);
        },

        async submitProgramStep() {
            this.error = '';
            if (!this.programName.trim()) {
                this.error = 'Program name is required.';
                return;
            }
            this.busy = true;
            try {
                const body = {
                    name: this.programName.trim(),
                    description: this.programDescription.trim() || null,
                };
                const json = this.mode === 'edit' && this.programId
                    ? await this.patchJson(this.programUpdateUrl(), body)
                    : await this.postJson(this.urls.program, body);
                this.programId = json.program_id;
                this.programName = json.name || this.programName;
                this.syncSeasonState();
                this.step = 2;
                this.$nextTick(() => this.syncSeasonNameSuggestion());
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },

        async addSeason() {
            this.error = '';
            if (!this.programId) {
                this.error = 'Save the program first.';
                return;
            }
            const start = this.activeStartMonth;
            const end = parseInt(this.seasonEndMonth, 10);
            if (!start || !this.monthsInSeason(start, end).length) {
                this.error = 'Choose a valid start and end month for this season.';
                return;
            }
            const label = this.seasonName.trim();
            if (!label) {
                this.error = 'Enter a name for this season.';
                return;
            }
            this.busy = true;
            try {
                const json = await this.postJson(this.urls.season, {
                    program_id: this.programId,
                    label,
                    start_month: start,
                    end_month: end,
                });
                this.seasons.push({
                    id: json.season_id,
                    label: json.label,
                    start_month: json.start_month,
                    end_month: json.end_month,
                });
                this.monthsComplete = json.months_complete;
                this.seasonName = '';
                this._lastSuggestedSeasonName = '';
                this.syncSeasonState();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },

        goToSitesStep() {
            this.error = '';
            if (!this.monthsComplete) {
                this.error = 'Add seasons until all twelve months are covered in order.';
                return;
            }
            this.step = 3;
        },

        async loadContinents() {
            if (this.continents.length > 0) {
                return;
            }
            try {
                const res = await fetch(this.urls.continents, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                this.continents = json.data || [];
            } catch {
                this.continents = [];
            }
        },

        async loadCountries() {
            this.countries = [];
            this.siteDraft.country = '';
            if (!this.siteDraft.continent) {
                return;
            }
            try {
                const url = this.urls.countries + '?continent=' + encodeURIComponent(this.siteDraft.continent);
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                this.countries = json.data || [];
            } catch {
                this.countries = [];
            }
        },

        async addSite() {
            this.error = '';
            if (!this.programId) {
                this.error = 'Program is missing.';
                return;
            }
            const d = this.siteDraft;
            if (!d.continent || !d.country || !d.name.trim()) {
                this.error = 'Continent, country, and site name are required.';
                return;
            }
            this.busy = true;
            try {
                const json = await this.postJson(this.urls.site, {
                    program_id: this.programId,
                    continent: d.continent,
                    country: d.country,
                    county: d.county || null,
                    ward: d.ward || null,
                    name: d.name.trim(),
                    primary_language: d.primary_language || null,
                });
                this.sites.push({ id: json.site_id, name: json.name });
                this.siteDraft.name = '';
                this.siteDraft.county = '';
                this.siteDraft.ward = '';
                this.siteDraft.primary_language = '';
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },

        finishWizard() {
            if (this.sites.length === 0) {
                this.error = 'Add at least one site for this program.';
                return;
            }
            this.setPageScrollLocked(false);
            window.location.reload();
        },
    }));
}
