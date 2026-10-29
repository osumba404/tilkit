import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { messageFromApiResponse } from './json-api-error';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const DEFAULT_MAP_CENTER = [-1.2921, 36.8219];
const DEFAULT_MAP_ZOOM = 11;

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
        sites: [],
        activeSiteId: null,
        mapSiteId: null,
        seasonStartMonth: 1,
        seasonEndMonth: 1,
        seasonName: '',
        monthShort: config.monthShort || {},
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
        studyMap: null,
        studyLayer: null,
        studyPeerLayer: null,
        studyPoints: [],
        studyMapStatus: 'Tap the map to add corners of the study area.',
        studyMapFullscreenFallback: false,
        studyMapIsFullscreen: false,

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

            this.$watch('step', (value) => {
                if (value === 4) {
                    this.$nextTick(() => this.initStudyMap());
                } else {
                    this.exitStudyMapFullscreen();
                    this.destroyStudyMap();
                }
            });

            document.addEventListener('fullscreenchange', () => {
                this.syncStudyMapFullscreenState();
            });
        },

        get modalTitle() {
            return this.mode === 'edit' ? 'Edit program setup' : 'New program setup';
        },

        get activeSite() {
            return this.sites.find((s) => s.id === this.activeSiteId) || null;
        },

        get activeSeasons() {
            return this.activeSite?.seasons || [];
        },

        get activeMonthsComplete() {
            return this.siteMonthsComplete(this.activeSite);
        },

        get mapSite() {
            return this.sites.find((s) => s.id === this.mapSiteId) || null;
        },

        get monthGrid() {
            const covered = this.coverageMapForSite(this.activeSite);
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
            return this.activeSeasons.length === 0;
        },

        get chainedStartMonth() {
            if (this.activeSeasons.length === 0) {
                return null;
            }
            const last = this.activeSeasons[this.activeSeasons.length - 1];

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
            const covered = this.coverageMapForSite(this.activeSite);
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

        siteLabelForSeasonStep(site) {
            const done = this.siteMonthsComplete(site) ? ' ✓' : '';
            return `${site.name}${done}`;
        },

        siteLabelForMapStep(site) {
            const done = site.mapSaved ? ' ✓' : '';
            return `${site.name}${done}`;
        },

        siteMonthsComplete(site) {
            if (!site) {
                return false;
            }
            const map = this.coverageMapForSeasons(site.seasons || []);

            return Object.keys(map).every((k) => map[k]);
        },

        allSitesSeasonsComplete() {
            return this.sites.length > 0 && this.sites.every((site) => this.siteMonthsComplete(site));
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

        coverageMapForSite(site) {
            return this.coverageMapForSeasons(site?.seasons || []);
        },

        coverageMapForSeasons(seasons) {
            const map = {};
            for (let m = 1; m <= 12; m++) {
                map[m] = false;
            }
            seasons.forEach((season) => {
                this.monthsInSeason(season.start_month, season.end_month).forEach((m) => {
                    map[m] = true;
                });
            });

            return map;
        },

        syncSeasonState() {
            if (this.activeMonthsComplete) {
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

        onActiveSiteChange() {
            this.seasonName = '';
            this._lastSuggestedSeasonName = '';
            this.syncSeasonState();
            this.$nextTick(() => this.syncSeasonNameSuggestion());
        },

        programUpdateUrl() {
            return this.urls.programUpdate.replace(/\/programs\/0(\/|$|\?)/, `/programs/${this.programId}$1`);
        },

        siteUpdateUrl(siteId) {
            return this.urls.siteUpdate.replace(/\/sites\/0(\/|$|\?)/, `/sites/${siteId}$1`);
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

            const nestedSites = payload.sites || [];
            if (nestedSites.length > 0 && nestedSites[0].seasons) {
                this.sites = nestedSites.map((site) => this.normalizeSiteFromPayload(site));
            } else {
                this.sites = (payload.sites || []).map((site) => ({
                    ...this.normalizeSiteFromPayload({ ...site, seasons: [], has_study_area: false }),
                    seasons: [],
                }));
                (payload.seasons || []).forEach((s) => {
                    const target = this.sites.find((site) => site.id === s.site_id) || this.sites[0];
                    if (target) {
                        target.seasons.push({
                            id: s.id,
                            label: s.label,
                            start_month: s.start_month,
                            end_month: s.end_month,
                        });
                    }
                });
            }

            this.activeSiteId = this.sites[0]?.id ?? null;
            this.mapSiteId = this.sites[0]?.id ?? null;
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
            this.exitStudyMapFullscreen();
            this.destroyStudyMap();
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
            this.sites = [];
            this.activeSiteId = null;
            this.mapSiteId = null;
            this.seasonStartMonth = 1;
            this.seasonEndMonth = 1;
            this.seasonName = '';
            this.studyPoints = [];
            this.studyMapStatus = 'Tap the map to add corners of the study area.';
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
                this.step = 2;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },

        goToSeasonsStep() {
            this.error = '';
            if (this.sites.length === 0) {
                this.error = 'Add at least one site for this program.';
                return;
            }
            if (!this.activeSiteId) {
                this.activeSiteId = this.sites[0].id;
            }
            this.syncSeasonState();
            this.step = 3;
            this.$nextTick(() => this.syncSeasonNameSuggestion());
        },

        goToMapStep() {
            this.error = '';
            if (!this.allSitesSeasonsComplete()) {
                this.error = 'Add seasons for each site until all twelve months are covered.';
                return;
            }
            this.mapSiteId = this.sites[0]?.id ?? null;
            this.step = 4;
        },

        normalizeSiteFromPayload(site) {
            return {
                id: site.id,
                name: site.name,
                continent: site.continent ?? null,
                country: site.country ?? null,
                country_name: site.country_name ?? null,
                county: site.county ?? null,
                ward: site.ward ?? null,
                seasons: (site.seasons || []).map((s) => ({
                    id: s.id,
                    label: s.label,
                    start_month: s.start_month,
                    end_month: s.end_month,
                })),
                mapSaved: Boolean(site.has_study_area),
                latitude: site.latitude ?? null,
                longitude: site.longitude ?? null,
                study_area_geojson: site.study_area_geojson ?? null,
                studyDraftPoints: null,
                shapeUnlocked: false,
            };
        },

        isStudyShapeLocked(site = this.mapSite) {
            if (!site) {
                return false;
            }
            if (site.shapeUnlocked) {
                return false;
            }
            if (Array.isArray(site.studyDraftPoints) && site.studyDraftPoints.length > 0) {
                return false;
            }

            return Boolean(site.mapSaved);
        },

        unlockStudyShape(site = this.mapSite) {
            if (!site) {
                return;
            }
            site.shapeUnlocked = true;
            site.studyDraftPoints = null;
            this.loadStudyPointsFromSaved(site);
            this.persistStudyDraftForCurrentSite();
            this.redrawStudyMap();
        },

        loadStudyPointsFromSaved(site) {
            this.studyPoints = [];
            if (!site) {
                return;
            }
            if (site.study_area_geojson?.type === 'Polygon' && site.study_area_geojson.coordinates?.[0]) {
                const ring = site.study_area_geojson.coordinates[0];
                ring.forEach(([lng, lat], i) => {
                    if (i === ring.length - 1 && ring.length > 1 && lng === ring[0][0] && lat === ring[0][1]) {
                        return;
                    }
                    this.studyPoints.push({ lat, lng });
                });
            } else if (site.latitude != null && site.longitude != null) {
                this.studyPoints.push({ lat: site.latitude, lng: site.longitude });
            }
        },

        selectSiteForMapEdit(site, { unlockShape = null } = {}) {
            this.persistStudyDraftForCurrentSite();
            this.mapSiteId = site.id;
            if (unlockShape === true) {
                site.shapeUnlocked = true;
            } else if (unlockShape === false) {
                site.shapeUnlocked = false;
            } else {
                site.shapeUnlocked = !site.mapSaved;
            }
            this.onMapSiteChange();
        },

        async deleteStudyArea(site) {
            if (!site) {
                return;
            }
            this.busy = true;
            this.error = '';
            try {
                await this.patchJson(this.siteUpdateUrl(site.id), { clear_study_area: true });
                site.mapSaved = false;
                site.latitude = null;
                site.longitude = null;
                site.study_area_geojson = null;
                site.studyDraftPoints = null;
                site.shapeUnlocked = true;
                if (site.id === this.mapSiteId) {
                    this.studyPoints = [];
                    this.redrawStudyMap();
                } else {
                    this.drawPeerSites();
                }
                this.studyMap?.closePopup();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },

        appendDeleteStudyAreaButton(actions, site) {
            if (!site.mapSaved) {
                return;
            }
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'tk-btn-secondary text-xs py-2 w-full text-red-700 dark:text-red-300';
            deleteBtn.textContent = 'Delete study area';
            deleteBtn.addEventListener('click', () => {
                if (!window.confirm(`Remove the saved study area for “${site.name}”? You can draw a new one afterward.`)) {
                    return;
                }
                this.deleteStudyArea(site);
            });
            actions.appendChild(deleteBtn);
        },

        onMapSiteChange() {
            this.persistStudyDraftForCurrentSite();
            this.loadStudyPointsForCurrentSite();
            this.redrawStudyMap();
            this.drawPeerSites();
            const site = this.mapSite;
            if (site && this.studyMap && site.latitude != null && site.longitude != null) {
                this.studyMap.setView([site.latitude, site.longitude], Math.max(this.studyMap.getZoom(), 12));
            }
        },

        persistStudyDraftForCurrentSite() {
            const site = this.mapSite;
            if (!site) {
                return;
            }
            site.studyDraftPoints = this.studyPoints.map((p) => ({ ...p }));
        },

        loadStudyPointsForCurrentSite() {
            const site = this.mapSite;
            if (!site) {
                this.studyPoints = [];
                return;
            }
            if (Array.isArray(site.studyDraftPoints)) {
                this.studyPoints = site.studyDraftPoints.map((p) => ({ ...p }));
                return;
            }
            if (this.isStudyShapeLocked(site)) {
                this.studyPoints = [];
                return;
            }
            this.loadStudyPointsFromSaved(site);
        },

        siteMapAnchor(site) {
            if (site.id === this.mapSiteId && this.studyPoints.length > 0 && !this.isStudyShapeLocked(site)) {
                const lats = this.studyPoints.map((p) => p.lat);
                const lngs = this.studyPoints.map((p) => p.lng);
                return {
                    lat: lats.reduce((a, b) => a + b, 0) / lats.length,
                    lng: lngs.reduce((a, b) => a + b, 0) / lngs.length,
                };
            }
            if (site.latitude != null && site.longitude != null) {
                return { lat: site.latitude, lng: site.longitude };
            }
            if (site.study_area_geojson?.type === 'Polygon' && site.study_area_geojson.coordinates?.[0]?.length) {
                const ring = site.study_area_geojson.coordinates[0];
                const points = ring.slice(0, -1).length ? ring.slice(0, -1) : ring;
                const lats = points.map((c) => c[1]);
                const lngs = points.map((c) => c[0]);
                return {
                    lat: lats.reduce((a, b) => a + b, 0) / lats.length,
                    lng: lngs.reduce((a, b) => a + b, 0) / lngs.length,
                };
            }
            return null;
        },

        siteTooltipHtml(site, { isCurrent = false, includeClickHint = true } = {}) {
            const place = [site.county, site.country_name || site.country, site.continent].filter(Boolean).join(' · ');
            const seasonLabels = (site.seasons || []).map((s) => s.label).join(', ') || 'No seasons yet';
            const mapLine = site.mapSaved ? 'Study area saved on map' : 'Study area not saved yet';
            const role = isCurrent ? 'Selected for editing' : 'Saved site in this program';
            const hint = includeClickHint ? '<div class="tk-wizard-site-tooltip-muted mt-1">Click marker for actions</div>' : '';

            return `
                <div class="tk-wizard-site-tooltip-title">${this.escapeHtml(site.name)}</div>
                <div class="tk-wizard-site-tooltip-muted">${this.escapeHtml(role)}</div>
                ${place ? `<div class="tk-wizard-site-tooltip-muted">${this.escapeHtml(place)}</div>` : ''}
                <div class="tk-wizard-site-tooltip-muted">Seasons: ${this.escapeHtml(seasonLabels)}</div>
                <div class="tk-wizard-site-tooltip-muted">${mapLine}</div>
                ${hint}
            `;
        },

        openSiteMarkerPopup(site, marker, isCurrent) {
            if (!this.studyMap || !marker) {
                return;
            }

            const body = document.createElement('div');
            body.className = 'tk-wizard-site-popup-body';

            const info = document.createElement('div');
            info.innerHTML = this.siteTooltipHtml(site, { isCurrent, includeClickHint: false });
            body.appendChild(info);

            const actions = document.createElement('div');
            actions.className = 'tk-wizard-site-popup-actions';

            const locked = site.mapSaved && !site.shapeUnlocked;

            if (!isCurrent) {
                const editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'tk-btn-primary text-xs py-2 w-full';
                editBtn.textContent = locked ? 'View / edit study area' : 'Edit study area';
                editBtn.addEventListener('click', () => {
                    this.selectSiteForMapEdit(site, { unlockShape: false });
                    this.studyMap.closePopup();
                });
                actions.appendChild(editBtn);
            } else if (locked) {
                const editShapeBtn = document.createElement('button');
                editShapeBtn.type = 'button';
                editShapeBtn.className = 'tk-btn-primary text-xs py-2 w-full';
                editShapeBtn.textContent = 'Edit shape';
                editShapeBtn.addEventListener('click', () => {
                    this.unlockStudyShape(site);
                    this.studyMap.closePopup();
                });
                actions.appendChild(editShapeBtn);
            } else {
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'tk-btn-secondary text-xs py-2 w-full';
                clearBtn.textContent = 'Clear corners / pin';
                clearBtn.addEventListener('click', () => {
                    this.studyMapClear();
                    this.studyMap.closePopup();
                });
                actions.appendChild(clearBtn);

                const saveBtn = document.createElement('button');
                saveBtn.type = 'button';
                saveBtn.className = 'tk-btn-primary text-xs py-2 w-full';
                saveBtn.textContent = 'Save study area';
                saveBtn.addEventListener('click', () => {
                    this.saveStudyAreaStep(false).then(() => {
                        this.studyMap?.closePopup();
                    });
                });
                actions.appendChild(saveBtn);
            }

            this.appendDeleteStudyAreaButton(actions, site);

            const centerBtn = document.createElement('button');
            centerBtn.type = 'button';
            centerBtn.className = 'tk-btn-secondary text-xs py-2 w-full';
            centerBtn.textContent = 'Center map here';
            centerBtn.addEventListener('click', () => {
                const latlng = marker.getLatLng?.();
                if (latlng) {
                    this.studyMap.setView(latlng, Math.max(this.studyMap.getZoom(), 13));
                }
                this.studyMap.closePopup();
            });
            actions.appendChild(centerBtn);

            body.appendChild(actions);

            marker
                .bindPopup(body, {
                    className: 'tk-wizard-site-popup',
                    maxWidth: 300,
                    minWidth: 220,
                    autoClose: true,
                    closeOnClick: false,
                })
                .openPopup();
        },

        escapeHtml(text) {
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },

        async addSeason() {
            this.error = '';
            if (!this.programId || !this.activeSiteId) {
                this.error = 'Save the program and choose a site first.';
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
                    site_id: this.activeSiteId,
                    label,
                    start_month: start,
                    end_month: end,
                });
                const site = this.sites.find((s) => s.id === this.activeSiteId);
                if (site) {
                    site.seasons.push({
                        id: json.season_id,
                        label: json.label,
                        start_month: json.start_month,
                        end_month: json.end_month,
                    });
                }
                this.seasonName = '';
                this._lastSuggestedSeasonName = '';
                this.syncSeasonState();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
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
                this.sites.push({
                    id: json.site_id,
                    name: json.name,
                    continent: d.continent,
                    country: d.country,
                    country_name: null,
                    county: d.county || null,
                    ward: d.ward || null,
                    seasons: [],
                    mapSaved: false,
                    latitude: null,
                    longitude: null,
                    study_area_geojson: null,
                    studyDraftPoints: null,
                    shapeUnlocked: true,
                });
                if (!this.activeSiteId) {
                    this.activeSiteId = json.site_id;
                }
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

        initStudyMap() {
            this.destroyStudyMap();
            const el = this.$refs.studyMapCanvas;
            if (!el || !this.mapSiteId) {
                return;
            }

            const site = this.mapSite;
            const center = site && site.latitude != null && site.longitude != null
                ? [site.latitude, site.longitude]
                : DEFAULT_MAP_CENTER;

            this.studyMap = L.map(el, { scrollWheelZoom: true }).setView(center, DEFAULT_MAP_ZOOM);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(this.studyMap);
            this.studyLayer = L.layerGroup().addTo(this.studyMap);
            this.studyPeerLayer = L.layerGroup().addTo(this.studyMap);

            this.loadStudyPointsForCurrentSite();

            this.studyMap.on('click', (e) => {
                if (this.isStudyShapeLocked()) {
                    return;
                }
                if (this.studyPoints.length === 1 && this.studyPoints[0].singlePin) {
                    this.studyPoints = [];
                }
                this.studyPoints.push({ lat: e.latlng.lat, lng: e.latlng.lng });
                this.persistStudyDraftForCurrentSite();
                this.redrawStudyMap();
            });

            this.redrawStudyMap();
            this.drawPeerSites();
            setTimeout(() => this.studyMap?.invalidateSize(), 200);
        },

        destroyStudyMap() {
            if (this.studyMap) {
                this.studyMap.off();
                this.studyMap.remove();
                this.studyMap = null;
                this.studyLayer = null;
                this.studyPeerLayer = null;
            }
        },

        drawPeerSites() {
            if (!this.studyPeerLayer || !this.studyMap) {
                return;
            }
            this.studyPeerLayer.clearLayers();

            this.sites.forEach((site) => {
                const isCurrent = site.id === this.mapSiteId;
                const anchor = this.siteMapAnchor(site);
                if (!anchor) {
                    return;
                }

                if (!isCurrent && site.study_area_geojson?.type === 'Polygon' && site.study_area_geojson.coordinates?.[0]) {
                    const ring = site.study_area_geojson.coordinates[0].map(([lng, lat]) => [lat, lng]);
                    L.polygon(ring, {
                        color: '#64748b',
                        weight: 1.5,
                        dashArray: '4 3',
                        fillOpacity: 0.06,
                    }).addTo(this.studyPeerLayer);
                }

                const marker = L.circleMarker([anchor.lat, anchor.lng], {
                    radius: isCurrent ? 9 : 7,
                    color: isCurrent ? '#0e7c86' : '#506274',
                    weight: 2,
                    fillColor: isCurrent ? '#0e7c86' : '#94a3b8',
                    fillOpacity: 0.85,
                }).addTo(this.studyPeerLayer);

                marker.bindTooltip(this.siteTooltipHtml(site, { isCurrent }), {
                    className: 'tk-wizard-site-tooltip',
                    direction: 'top',
                    offset: [0, -6],
                    opacity: 1,
                    sticky: false,
                    interactive: false,
                });

                marker.on('click', (event) => {
                    L.DomEvent.stopPropagation(event);
                    this.openSiteMarkerPopup(site, marker, isCurrent);
                });
            });
        },

        drawSavedStudyAreaOnLayer(site) {
            if (!this.studyLayer || !site?.study_area_geojson?.type) {
                return;
            }
            if (site.study_area_geojson.type === 'Polygon' && site.study_area_geojson.coordinates?.[0]) {
                const ring = site.study_area_geojson.coordinates[0].map(([lng, lat]) => [lat, lng]);
                L.polygon(ring, {
                    color: '#0e7c86',
                    fillOpacity: 0.12,
                    weight: 2,
                }).addTo(this.studyLayer);
            }
        },

        redrawStudyMap() {
            if (!this.studyLayer) {
                return;
            }
            this.studyLayer.clearLayers();

            const site = this.mapSite;
            if (site && this.isStudyShapeLocked(site)) {
                this.drawSavedStudyAreaOnLayer(site);
                this.studyMapStatus = 'Study area saved. Click the centre marker for actions — use “Edit shape” in the popup to change corners.';
                this.drawPeerSites();
                return;
            }

            if (this.studyPoints.length === 0) {
                this.studyMapStatus = 'Tap the map to add corners of the study area.';
                this.drawPeerSites();
                return;
            }

            const latlngs = this.studyPoints.map((p) => [p.lat, p.lng]);
            this.studyPoints.forEach((p, index) => {
                const marker = L.marker([p.lat, p.lng]).addTo(this.studyLayer);
                marker.bindTooltip('Click to remove this point', {
                    className: 'tk-wizard-site-tooltip',
                    direction: 'top',
                    opacity: 1,
                });
                marker.on('click', (event) => {
                    L.DomEvent.stopPropagation(event);
                    this.removeStudyPointAt(index);
                });
            });

            const removeHint = ' Click a pin to remove it, or use Undo corner / Clear.';

            if (this.studyPoints.length >= 3) {
                L.polygon(latlngs, { color: '#0e7c86', fillOpacity: 0.15, weight: 2 }).addTo(this.studyLayer);
                this.studyMapStatus = `${this.studyPoints.length} corners — ready to save.${removeHint}`;
            } else if (this.studyPoints.length === 2) {
                L.polyline(latlngs, { color: '#506274', dashArray: '4 4' }).addTo(this.studyLayer);
                this.studyMapStatus = `Add one more corner, or clear and use a single pin.${removeHint}`;
            } else {
                this.studyMapStatus = `One point set. Add more corners or save as center pin.${removeHint}`;
            }
            this.drawPeerSites();
        },

        studyMapToggleFullscreen() {
            const host = this.$refs.studyMapHost;
            if (!host) {
                return;
            }
            if (document.fullscreenElement === host) {
                document.exitFullscreen?.();
                return;
            }
            if (this.studyMapFullscreenFallback) {
                this.studyMapFullscreenFallback = false;
                this.syncStudyMapFullscreenState();
                return;
            }
            const request = host.requestFullscreen?.() ?? host.webkitRequestFullscreen?.();
            if (request && typeof request.then === 'function') {
                request.catch(() => {
                    this.studyMapFullscreenFallback = true;
                    this.syncStudyMapFullscreenState();
                });
            } else {
                this.studyMapFullscreenFallback = true;
                this.syncStudyMapFullscreenState();
            }
        },

        exitStudyMapFullscreen() {
            if (document.fullscreenElement === this.$refs.studyMapHost) {
                document.exitFullscreen?.();
            }
            this.studyMapFullscreenFallback = false;
            this.syncStudyMapFullscreenState();
        },

        syncStudyMapFullscreenState() {
            const host = this.$refs.studyMapHost;
            this.studyMapIsFullscreen = Boolean(
                (host && document.fullscreenElement === host) || this.studyMapFullscreenFallback,
            );
            setTimeout(() => this.studyMap?.invalidateSize(), 150);
        },

        studyMapLocate() {
            if (this.isStudyShapeLocked()) {
                this.studyMapStatus = 'Click the centre marker and choose “Edit shape” to change a saved study area.';
                return;
            }
            if (!navigator.geolocation || !this.studyMap) {
                this.studyMapStatus = 'Geolocation is not available.';
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const { latitude, longitude } = pos.coords;
                    this.studyPoints = [{ lat: latitude, lng: longitude, singlePin: true }];
                    this.persistStudyDraftForCurrentSite();
                    this.redrawStudyMap();
                    this.studyMap.setView([latitude, longitude], 14);
                },
                () => {
                    this.studyMapStatus = 'Could not get location. Tap the map instead.';
                },
                { enableHighAccuracy: true, timeout: 15000 },
            );
        },

        removeStudyPointAt(index) {
            if (index < 0 || index >= this.studyPoints.length) {
                return;
            }
            this.studyPoints.splice(index, 1);
            this.persistStudyDraftForCurrentSite();
            this.redrawStudyMap();
        },

        studyMapUndo() {
            this.studyPoints.pop();
            this.persistStudyDraftForCurrentSite();
            this.redrawStudyMap();
        },

        studyMapClear() {
            this.studyPoints = [];
            this.persistStudyDraftForCurrentSite();
            this.redrawStudyMap();
        },

        buildStudyAreaPayload() {
            if (this.studyPoints.length >= 3) {
                const ring = this.studyPoints.map((p) => [p.lng, p.lat]);
                const first = ring[0];
                ring.push([first[0], first[1]]);
                const lats = this.studyPoints.map((p) => p.lat);
                const lngs = this.studyPoints.map((p) => p.lng);
                const centroidLat = lats.reduce((a, b) => a + b, 0) / lats.length;
                const centroidLng = lngs.reduce((a, b) => a + b, 0) / lngs.length;

                return {
                    latitude: centroidLat,
                    longitude: centroidLng,
                    study_area_geojson: {
                        type: 'Polygon',
                        coordinates: [ring],
                        properties: { point_count: this.studyPoints.length },
                    },
                };
            }
            if (this.studyPoints.length === 1) {
                const p = this.studyPoints[0];

                return {
                    latitude: p.lat,
                    longitude: p.lng,
                    study_area_geojson: null,
                };
            }

            return null;
        },

        async saveStudyAreaStep(finish = false) {
            this.error = '';
            const site = this.mapSite;
            if (!site) {
                return;
            }
            const payload = this.buildStudyAreaPayload();
            if (!payload) {
                if (finish && site.mapSaved) {
                    this.finishWizard();
                    return;
                }
                this.error = 'Add at least one map point (3+ corners for an area, or one pin for the center), or save another site first.';
                return;
            }
            this.busy = true;
            try {
                await this.patchJson(this.siteUpdateUrl(site.id), payload);
                site.mapSaved = true;
                site.latitude = payload.latitude;
                site.longitude = payload.longitude;
                site.study_area_geojson = payload.study_area_geojson;
                site.studyDraftPoints = null;
                site.shapeUnlocked = false;
                this.loadStudyPointsForCurrentSite();
                this.drawPeerSites();

                if (finish) {
                    this.finishWizard();
                }
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },

        finishWizard() {
            this.exitStudyMapFullscreen();
            this.destroyStudyMap();
            this.setPageScrollLocked(false);
            window.location.reload();
        },
    }));
}
