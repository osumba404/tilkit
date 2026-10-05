import L from 'leaflet';
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

document.querySelectorAll('[data-tk-geo]').forEach((root) => {
    new GeoFieldWidget(root);
});

class GeoFieldWidget {
    constructor(root) {
        this.root = root;
        this.type = root.dataset.tkGeoType;
        this.input = root.querySelector('input[type="hidden"]');
        this.statusEl = root.querySelector('[data-tk-geo-status]');
        this.mapEl = root.querySelector('[data-tk-geo-map]');
        this.points = [];
        this.map = L.map(this.mapEl, { scrollWheelZoom: true }).setView(DEFAULT_CENTER, 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19,
        }).addTo(this.map);

        this.layerGroup = L.layerGroup().addTo(this.map);
        this.restoreFromInput();

        this.map.on('click', (e) => this.addPoint(e.latlng.lat, e.latlng.lng, null));

        root.querySelector('[data-tk-geo-locate]')?.addEventListener('click', () => this.locate());
        root.querySelector('[data-tk-geo-undo]')?.addEventListener('click', () => this.undo());
        root.querySelector('[data-tk-geo-clear]')?.addEventListener('click', () => this.clear());

        setTimeout(() => this.map.invalidateSize(), 200);
    }

    restoreFromInput() {
        if (!this.input.value) {
            return;
        }
        try {
            const data = JSON.parse(this.input.value);
            if (data.type === 'Point') {
                const [lng, lat] = data.coordinates;
                this.setPoint(lat, lng, data.properties ?? {});
            } else if (data.type === 'LineString') {
                data.coordinates.forEach(([lng, lat]) => this.points.push({ lat, lng, props: {} }));
                this.redraw();
            } else if (data.type === 'Polygon' && data.coordinates?.[0]) {
                const ring = data.coordinates[0];
                ring.forEach(([lng, lat], i) => {
                    if (i === ring.length - 1 && ring.length > 1 && lng === ring[0][0] && lat === ring[0][1]) {
                        return;
                    }
                    this.points.push({ lat, lng, props: {} });
                });
                this.redraw();
            }
        } catch {
            /* ignore invalid old input */
        }
    }

    locate() {
        if (!navigator.geolocation) {
            this.setStatus('Geolocation is not supported on this device.');
            return;
        }
        this.setStatus('Getting location…');
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const { latitude, longitude, accuracy, altitude } = pos.coords;
                const props = {
                    accuracy: accuracy ?? null,
                    altitude: altitude ?? null,
                    source: 'gps',
                };
                if (this.type === 'geopoint') {
                    this.setPoint(latitude, longitude, props);
                    this.map.setView([latitude, longitude], 16);
                } else {
                    this.addPoint(latitude, longitude, props);
                    this.map.setView([latitude, longitude], Math.max(this.map.getZoom(), 15));
                }
            },
            () => this.setStatus('Could not get location. Allow GPS or tap the map.'),
            { enableHighAccuracy: true, timeout: 15000 },
        );
    }

    setPoint(lat, lng, props) {
        this.points = [{ lat, lng, props: props ?? {} }];
        this.redraw();
        this.syncInput();
    }

    addPoint(lat, lng, props) {
        if (this.type === 'geopoint') {
            this.setPoint(lat, lng, props);
            return;
        }
        this.points.push({ lat, lng, props: props ?? {} });
        this.redraw();
        this.syncInput();
    }

    undo() {
        if (this.type === 'geopoint') {
            this.clear();
            return;
        }
        this.points.pop();
        this.redraw();
        this.syncInput();
    }

    clear() {
        this.points = [];
        this.layerGroup.clearLayers();
        this.input.value = '';
        this.setStatus('No location captured yet.');
    }

    redraw() {
        this.layerGroup.clearLayers();
        if (this.points.length === 0) {
            this.input.value = '';
            this.setStatus('No location captured yet.');
            return;
        }

        const latlngs = this.points.map((p) => [p.lat, p.lng]);

        if (this.type === 'geopoint') {
            const p = this.points[0];
            L.marker([p.lat, p.lng]).addTo(this.layerGroup);
            this.setStatus(this.formatPoint(p));
            return;
        }

        this.points.forEach((p) => L.marker([p.lat, p.lng]).addTo(this.layerGroup));

        if (this.type === 'geotrace' && latlngs.length >= 2) {
            L.polyline(latlngs, { color: '#0e7c86', weight: 3 }).addTo(this.layerGroup);
            this.setStatus(`${latlngs.length} points in trace`);
        } else if (this.type === 'geotrace') {
            this.setStatus(`${latlngs.length} point(s). Need at least 2 for a trace.`);
        }

        if (this.type === 'geoshape' && latlngs.length >= 3) {
            L.polygon(latlngs, { color: '#0e7c86', fillOpacity: 0.15, weight: 2 }).addTo(this.layerGroup);
            this.setStatus(`${latlngs.length} corners (area ready)`);
        } else if (this.type === 'geoshape') {
            if (latlngs.length >= 2) {
                L.polyline(latlngs, { color: '#506274', dashArray: '4 4' }).addTo(this.layerGroup);
            }
            this.setStatus(`${latlngs.length} corner(s). Need at least 3 for a shape.`);
        }
    }

    syncInput() {
        if (this.type === 'geopoint') {
            if (this.points.length === 0) {
                this.input.value = '';
                return;
            }
            const p = this.points[0];
            const payload = {
                type: 'Point',
                coordinates: [p.lng, p.lat],
                properties: p.props,
            };
            this.input.value = JSON.stringify(payload);
            this.setStatus(this.formatPoint(p));
            return;
        }

        if (this.type === 'geotrace') {
            if (this.points.length < 2) {
                this.input.value = '';
                this.redraw();
                return;
            }
            const payload = {
                type: 'LineString',
                coordinates: this.points.map((p) => [p.lng, p.lat]),
                properties: { point_count: this.points.length },
            };
            this.input.value = JSON.stringify(payload);
            this.setStatus(`${this.points.length} points in trace`);
            return;
        }

        if (this.type === 'geoshape') {
            if (this.points.length < 3) {
                this.input.value = '';
                this.redraw();
                return;
            }
            const ring = this.points.map((p) => [p.lng, p.lat]);
            ring.push(ring[0]);
            const payload = {
                type: 'Polygon',
                coordinates: [ring],
                properties: { vertex_count: this.points.length },
            };
            this.input.value = JSON.stringify(payload);
            this.setStatus(`${this.points.length} corners (area ready)`);
        }
    }

    formatPoint(p) {
        const acc = p.props?.accuracy != null ? ` ±${Math.round(p.props.accuracy)} m` : '';
        return `${p.lat.toFixed(6)}, ${p.lng.toFixed(6)}${acc}`;
    }

    setStatus(text) {
        if (this.statusEl) {
            this.statusEl.textContent = text;
        }
    }
}

const form = document.querySelector('form[enctype="multipart/form-data"]');
form?.addEventListener('submit', (event) => {
    let blocked = false;
    document.querySelectorAll('[data-required-geo]').forEach((input) => {
        if (!input.value || input.value.trim() === '') {
            blocked = true;
            const widget = input.closest('[data-tk-geo]');
            const status = widget?.querySelector('[data-tk-geo-status]');
            if (status) {
                status.textContent = 'Please capture a location before submitting.';
                status.classList.add('text-red-600');
            }
        }
    });
    if (blocked) {
        event.preventDefault();
    }
});
