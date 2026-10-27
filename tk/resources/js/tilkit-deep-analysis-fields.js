/**
 * Field roles - which columns fit tables vs charts vs pie vs map, etc.
 */

export const FIELD_ROLE = {
    MEASURE: 'measure',
    DIMENSION: 'dimension',
    DIMENSION_PIE: 'dimension_pie',
    DATE: 'date',
    GEO_LAT: 'geo_lat',
    GEO_LON: 'geo_lon',
    TABLE: 'table',
    TABLE_ONLY: 'table_only',
};

export const ROLE_HINTS = {
    measure: 'Numbers - Y-axis, sums, averages, KPIs',
    dimension: 'Categories - bars, groups, matrix rows/columns',
    dimension_pie: 'Categories with ≤15 distinct values - best for pie/donut',
    date: 'Dates - timelines and trends',
    geo_lat: 'Latitude (or parsed from GPS)',
    geo_lon: 'Longitude (or parsed from GPS)',
    table: 'Any field - tables and snapshots',
    measure_or_dimension: 'Numbers or categories - scatter X',
};

const PIE_MAX_CATEGORIES = 15;
const HIGH_CARDINALITY = 35;

/**
 * @param {list} columns
 * @param {list} rows
 * @param {(id: string) => boolean} isNumericColumn
 */
export function enrichColumnRoles(columns, rows, isNumericColumn) {
    return columns.map((col) => {
        const roles = new Set(col.roles || [FIELD_ROLE.TABLE]);
        const values = rows.map((r) => r[col.id]).filter((v) => v !== null && v !== '');
        const unique = new Set(values.map((v) => String(v).slice(0, 120))).size;
        const numeric = isNumericColumn(col.id);

        if (numeric) {
            roles.add(FIELD_ROLE.MEASURE);
        }
        if (col.kind === 'date' || roles.has(FIELD_ROLE.DATE)) {
            roles.add(FIELD_ROLE.DATE);
            roles.add(FIELD_ROLE.DIMENSION);
        }
        if (!numeric || (numeric && unique <= PIE_MAX_CATEGORIES && unique > 0)) {
            roles.add(FIELD_ROLE.DIMENSION);
        }
        if (unique > 0 && unique <= PIE_MAX_CATEGORIES && (roles.has(FIELD_ROLE.DIMENSION) || col.kind === 'categorical')) {
            roles.add(FIELD_ROLE.DIMENSION_PIE);
        }
        if (unique > HIGH_CARDINALITY && !numeric) {
            roles.delete(FIELD_ROLE.DIMENSION_PIE);
        }
        if (col.id === '_latitude') {
            roles.add(FIELD_ROLE.GEO_LAT);
        }
        if (col.id === '_longitude') {
            roles.add(FIELD_ROLE.GEO_LON);
        }

        return {
            ...col,
            uniqueCount: unique,
            roles: [...roles],
        };
    });
}

/** @param {object} col @param {string} role */
export function columnHasRole(col, role) {
    if (!col?.roles) {
        return false;
    }
    if (role === 'measure_or_dimension') {
        return col.roles.includes(FIELD_ROLE.MEASURE) || col.roles.includes(FIELD_ROLE.DIMENSION);
    }
    if (role === 'numeric') {
        return col.roles.includes(FIELD_ROLE.MEASURE);
    }
    return col.roles.includes(role);
}

/**
 * @param {list} columns
 * @param {string|null} role
 * @param {(id: string) => boolean} isNumericColumn
 */
export function filterColumnsForRole(columns, role, isNumericColumn) {
    if (!role) {
        return [];
    }
    if (role === 'table') {
        return columns.filter((c) => !c.roles?.includes(FIELD_ROLE.TABLE_ONLY));
    }
    return columns.filter((c) => {
        if (c.roles?.includes(FIELD_ROLE.TABLE_ONLY)) {
            return false;
        }
        if (role === 'numeric') {
            return isNumericColumn(c.id);
        }
        return columnHasRole(c, role);
    });
}

/**
 * Resolve which role a dashboard picker needs.
 * @param {string} cardType
 * @param {string} fieldKey
 * @param {object} config
 * @returns {string|null} null = hide control
 */
export function pickerRoleFor(cardType, fieldKey, config = {}) {
    switch (cardType) {
        case 'kpi':
            if (fieldKey === 'customColumn') {
                return config.metric === 'column_agg' ? FIELD_ROLE.MEASURE : null;
            }
            return null;
        case 'chart': {
            const ct = config.chartType || 'bar';
            if (fieldKey === 'y') {
                if (ct === 'pie') {
                    return null;
                }
                return FIELD_ROLE.MEASURE;
            }
            if (fieldKey === 'x') {
                if (ct === 'histogram' || ct === 'box') {
                    return null;
                }
                if (ct === 'pie') {
                    return FIELD_ROLE.DIMENSION_PIE;
                }
                if (ct === 'scatter') {
                    return 'measure_or_dimension';
                }
                return FIELD_ROLE.DIMENSION;
            }
            return null;
        }
        case 'category_breakdown':
            return config.viz === 'bar' ? FIELD_ROLE.DIMENSION : FIELD_ROLE.DIMENSION_PIE;
        case 'timeline':
            if (fieldKey === 'dateCol') {
                return FIELD_ROLE.DATE;
            }
            if (fieldKey === 'valueCol') {
                return FIELD_ROLE.MEASURE;
            }
            return null;
        case 'heatmap':
            return FIELD_ROLE.DIMENSION;
        case 'map':
            if (fieldKey === 'lat') {
                return FIELD_ROLE.GEO_LAT;
            }
            if (fieldKey === 'lon') {
                return FIELD_ROLE.GEO_LON;
            }
            if (fieldKey === 'color') {
                return FIELD_ROLE.DIMENSION;
            }
            return null;
        case 'leaderboard':
            return FIELD_ROLE.DIMENSION;
        case 'field_snapshot':
        case 'data_table':
            return 'table';
        default:
            return 'table';
    }
}

/** @returns {string} */
export function roleHint(role, matchCount) {
    const base = ROLE_HINTS[role] || 'Compatible fields';
    return `${base} · ${matchCount} available`;
}

/**
 * Build <option> list; optional optgroups for measure_or_dimension.
 */
export function buildSelectOptions(columns, role, selected, allowEmpty, isNumericColumn) {
    const empty = allowEmpty ? `<option value="" ${selected === '' ? 'selected' : ''}>-</option>` : '';
    const list = filterColumnsForRole(columns, role, isNumericColumn);

    if (role === 'measure_or_dimension') {
        const measures = list.filter((c) => isNumericColumn(c.id));
        const dims = list.filter((c) => !isNumericColumn(c.id) && columnHasRole(c, FIELD_ROLE.DIMENSION));
        const opt = (c) =>
            `<option value="${escapeAttr(c.id)}" ${c.id === selected ? 'selected' : ''}>${escapeHtml(c.label)}${c.uniqueCount != null ? ` (${c.uniqueCount})` : ''}</option>`;
        return (
            empty +
            (dims.length ? `<optgroup label="Categories">${dims.map(opt).join('')}</optgroup>` : '') +
            (measures.length ? `<optgroup label="Numbers">${measures.map(opt).join('')}</optgroup>` : '')
        );
    }

    if (list.length === 0) {
        return `${empty}<option value="" disabled>No matching fields in this dataset</option>`;
    }

    return (
        empty +
        list
            .map((c) => {
                const suffix =
                    role === FIELD_ROLE.DIMENSION_PIE && c.uniqueCount != null ? ` · ${c.uniqueCount} values` : '';
                return `<option value="${escapeAttr(c.id)}" ${c.id === selected ? 'selected' : ''}>${escapeHtml(c.label)}${suffix}</option>`;
            })
            .join('')
    );
}

function escapeHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function escapeAttr(s) {
    return escapeHtml(s).replace(/"/g, '&quot;');
}

/** Visual types that need at least one measure / dimension in data */
export function galleryAvailability(columns, isNumericColumn) {
    const hasMeasure = columns.some((c) => isNumericColumn(c.id));
    const hasDimension = columns.some((c) => columnHasRole(c, FIELD_ROLE.DIMENSION));
    const hasPie = columns.some((c) => columnHasRole(c, FIELD_ROLE.DIMENSION_PIE));
    const hasDate = columns.some((c) => columnHasRole(c, FIELD_ROLE.DATE));
    const hasGeo =
        columns.some((c) => columnHasRole(c, FIELD_ROLE.GEO_LAT)) &&
        columns.some((c) => columnHasRole(c, FIELD_ROLE.GEO_LON));

    return {
        kpi: hasMeasure || columns.length > 0,
        chart: hasMeasure && hasDimension,
        category_breakdown: hasPie || hasDimension,
        timeline: hasDate,
        heatmap: hasDimension,
        funnel: columns.length > 0,
        map: hasGeo,
        leaderboard: hasDimension,
        field_snapshot: true,
        data_table: true,
    };
}
