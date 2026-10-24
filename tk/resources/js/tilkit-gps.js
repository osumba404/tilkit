import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const DEFAULT_CENTER = [-1.2921, 36.8219];
const DEFAULT_ZOOM = 11;

document.querySelectorAll('[data-tk-gps-map]').forEach((root) => {
    new TilkitGpsMap(root);
});

function initTilkitGpsViews(root = document) {
    root.querySelectorAll('[data-tk-gps-view]:not([data-tk-gps-inited])').forEach((canvas) => {
        canvas.setAttribute('data-tk-gps-inited', '1');
        new TilkitGpsView(canvas);
    });
}

initTilkitGpsViews();
window.tkInitTilkitGpsViews = initTilkitGpsViews;

class TilkitGpsMap {
    constructor(root) {
        this.root = root;
        this.form = root.closest('form');
        this.latInput = this.inputFor(root.dataset.latName);
        this.lngInput = this.inputFor(root.dataset.lngName);
        this.statusEl = root.querySelector('[data-tk-gps-status]');
        this.canvas = root.querySelector('[data-tk-gps-canvas]');

        if (!this.latInput || !this.lngInput || !this.canvas) {
            return;
        }

        const centerLat = this.parseCoord(root.dataset.centerLat);
        const centerLng = this.parseCoord(root.dataset.centerLng);
        const startLat = this.parseCoord(this.latInput.value) ?? centerLat;
        const startLng = this.parseCoord(this.lngInput.value) ?? centerLng;
        const view = startLat !== null && startLng !== null
            ? [startLat, startLng]
            : centerLat !== null && centerLng !== null
                ? [centerLat, centerLng]
                : DEFAULT_CENTER;
        const zoom = startLat !== null && startLng !== null ? 14 : DEFAULT_ZOOM;

        this.map = L.map(this.canvas, { scrollWheelZoom: true }).setView(view, zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(this.map);

        this.marker = null;
        if (startLat !== null && startLng !== null) {
            this.placeMarker(startLat, startLng, false);
        }

        this.map.on('click', (e) => this.placeMarker(e.latlng.lat, e.latlng.lng, true));

        root.querySelector('[data-tk-gps-locate]')?.addEventListener('click', () => this.locate());
        root.querySelector('[data-tk-gps-clear]')?.addEventListener('click', () => this.clear());

        this.latInput.addEventListener('change', () => this.syncFromInputs());
        this.lngInput.addEventListener('change', () => this.syncFromInputs());

        setTimeout(() => this.map.invalidateSize(), 250);
        window.addEventListener('load', () => this.map.invalidateSize(), { once: true });
    }

    inputFor(name) {
        if (!this.form || !name) {
            return null;
        }
        const named = this.form.elements.namedItem(name);
        if (named && named.tagName === 'INPUT') {
            return named;
        }

        return this.form.querySelector(`input[name="${name.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"]`);
    }

    parseCoord(value) {
        if (value === undefined || value === null || value === '') {
            return null;
        }
        const n = Number.parseFloat(String(value));
        return Number.isFinite(n) ? n : null;
    }

    placeMarker(lat, lng, pan) {
        const roundedLat = Math.round(lat * 1e6) / 1e6;
        const roundedLng = Math.round(lng * 1e6) / 1e6;

        if (this.marker) {
            this.marker.setLatLng([roundedLat, roundedLng]);
        } else {
            this.marker = L.marker([roundedLat, roundedLng], { draggable: true }).addTo(this.map);
            this.marker.on('dragend', () => {
                const pos = this.marker.getLatLng();
                this.writeInputs(pos.lat, pos.lng);
                this.setStatus(`Pin at ${this.formatPair(pos.lat, pos.lng)}`);
            });
        }

        this.writeInputs(roundedLat, roundedLng);
        if (pan) {
            this.map.setView([roundedLat, roundedLng], Math.max(this.map.getZoom(), 14));
        }
        this.setStatus(`Pin at ${this.formatPair(roundedLat, roundedLng)}`);
    }

    writeInputs(lat, lng) {
        this.latInput.value = lat;
        this.lngInput.value = lng;
    }

    syncFromInputs() {
        const lat = this.parseCoord(this.latInput.value);
        const lng = this.parseCoord(this.lngInput.value);
        if (lat === null || lng === null) {
            return;
        }
        this.placeMarker(lat, lng, true);
    }

    locate() {
        if (!navigator.geolocation) {
            this.setStatus('Geolocation is not supported in this browser.');
            return;
        }
        this.setStatus('Getting your location…');
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                this.placeMarker(pos.coords.latitude, pos.coords.longitude, true);
            },
            () => this.setStatus('Could not get GPS. Allow location access or tap the map.'),
            { enableHighAccuracy: true, timeout: 15000 },
        );
    }

    clear() {
        if (this.marker) {
            this.map.removeLayer(this.marker);
            this.marker = null;
        }
        this.latInput.value = '';
        this.lngInput.value = '';
        this.setStatus('Tap the map to place the sighting pin.');
    }

    formatPair(lat, lng) {
        return `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    }

    setStatus(message) {
        if (this.statusEl) {
            this.statusEl.textContent = message;
        }
    }
}

class TilkitGpsView {
    constructor(canvas) {
        this.canvas = canvas;
        let points = [];
        try {
            points = JSON.parse(canvas.dataset.points || '[]');
        } catch {
            points = [];
        }

        const valid = points.filter((p) => Number.isFinite(p.lat) && Number.isFinite(p.lng));
        const start = valid.length > 0
            ? [valid[0].lat, valid[0].lng]
            : DEFAULT_CENTER;
        const zoom = valid.length === 1 ? 13 : DEFAULT_ZOOM;

        this.map = L.map(canvas, { scrollWheelZoom: true }).setView(start, zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(this.map);

        this.layer = L.layerGroup().addTo(this.map);
        const bounds = [];

        valid.forEach((p) => {
            const color = this.colorForKind(p.kind);
            const marker = L.circleMarker([p.lat, p.lng], {
                radius: p.kind === 'site' ? 9 : 7,
                color,
                fillColor: color,
                fillOpacity: 0.85,
                weight: 2,
            }).addTo(this.layer);

            const link = p.href
                ? `<a href="${this.escapeAttr(p.href)}" class="text-tk-accent underline">Open record</a>`
                : '';
            marker.bindPopup(
                `<strong>${this.escapeHtml(p.label ?? 'Location')}</strong><br>`
                + `<span style="font-size:12px;color:#5b6573">${this.escapeHtml(p.detail ?? '')}</span>`
                + (link ? `<br>${link}` : ''),
            );
            bounds.push([p.lat, p.lng]);
        });

        if (bounds.length > 1) {
            this.map.fitBounds(bounds, { padding: [28, 28], maxZoom: 14 });
        }

        setTimeout(() => this.map.invalidateSize(), 250);
        window.addEventListener('load', () => this.map.invalidateSize(), { once: true });
    }

    colorForKind(kind) {
        if (kind === 'sighting') {
            return '#059669';
        }
        if (kind === 'site') {
            return '#64748b';
        }

        return '#0a7c94';
    }

    escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    escapeAttr(value) {
        return this.escapeHtml(value).replace(/'/g, '&#39;');
    }
}
