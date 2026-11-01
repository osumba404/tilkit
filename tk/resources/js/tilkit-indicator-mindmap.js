import cytoscape from 'cytoscape';
import dagre from 'cytoscape-dagre';

cytoscape.use(dagre);

function readPayload(host) {
    const el = host.querySelector('[data-tk-mindmap-json]');
    if (!el?.textContent?.trim()) {
        return null;
    }
    try {
        return JSON.parse(el.textContent);
    } catch (err) {
        console.error('[tilkit mindmap] Invalid JSON payload', err);
        return null;
    }
}

function showMountError(mount, message) {
    mount.innerHTML = `<p class="text-sm text-red-300 p-4">${message}</p>`;
}

const CY_STYLES = [
    {
        selector: 'node',
        style: {
            label: 'data(label)',
            'text-wrap': 'wrap',
            'text-max-width': '220px',
            'font-size': '11px',
            'font-family': 'system-ui, sans-serif',
            color: '#f8fafc',
            'text-valign': 'center',
            'text-halign': 'center',
            shape: 'round-rectangle',
            width: 'label',
            height: 'label',
            padding: '12px',
        },
    },
    {
        selector: 'node[kind = "root"]',
        style: {
            'background-color': '#6d28d9',
            'border-width': 2,
            'border-color': '#c4b5fd',
            'font-weight': 'bold',
        },
    },
    {
        selector: 'node[kind = "pillar"]',
        style: {
            'background-color': '#475569',
            'border-width': 2,
            'border-color': '#94a3b8',
        },
    },
    {
        selector: 'node[kind = "pillar"][active = "yes"]',
        style: {
            'border-color': '#38bdf8',
            'border-width': 3,
        },
    },
    {
        selector: 'node[kind = "type"]',
        style: {
            'background-color': '#047857',
            'border-width': 2,
            'border-color': '#34d399',
        },
    },
    {
        selector: 'node[kind = "type"][active = "yes"]',
        style: {
            'border-color': '#6ee7b7',
            'border-width': 3,
        },
    },
    {
        selector: 'node[kind = "subtype"]',
        style: {
            'background-color': '#115e59',
            'border-width': 2,
            'border-color': '#2dd4bf',
            'font-size': '10px',
        },
    },
    {
        selector: 'node[kind = "subtype"][active = "yes"]',
        style: {
            'border-color': '#99f6e4',
            'border-width': 3,
        },
    },
    {
        selector: 'node[kind = "code"]',
        style: {
            'background-color': '#064e3b',
            'border-width': 1,
            'border-color': '#10b981',
            'font-family': 'ui-monospace, monospace',
            'font-size': '9px',
        },
    },
    {
        selector: 'node[kind = "blurb"]',
        style: {
            'background-color': '#334155',
            'border-width': 1,
            'border-color': '#cbd5e1',
            'font-size': '10px',
            'text-max-width': '260px',
        },
    },
    {
        selector: 'node[warn = "yes"]',
        style: {
            'background-color': '#78350f',
            'border-color': '#fbbf24',
        },
    },
    {
        selector: 'edge',
        style: {
            width: 2.5,
            'line-color': '#38bdf8',
            'target-arrow-color': '#38bdf8',
            'target-arrow-shape': 'triangle',
            'curve-style': 'bezier',
            'arrow-scale': 0.85,
        },
    },
];

class IndicatorMindmapGraph {
    constructor(mount, payload) {
        this.mount = mount;
        this.payload = payload;
        this.codeBranchOpen = true;
        this.selectedPillar = 'code_categories';
        this.selectedTypeKey = null;
        this.selectedSubKey = null;
        this.pillarBlurbId = null;

        this.mount.innerHTML = '';

        try {
            this.cy = cytoscape({
                container: mount,
                elements: this.buildElements(),
                style: CY_STYLES,
                wheelSensitivity: 0.3,
                minZoom: 0.25,
                maxZoom: 2.5,
                boxSelectionEnabled: false,
                userPanningEnabled: true,
                userZoomingEnabled: true,
            });
        } catch (err) {
            console.error('[tilkit mindmap] cytoscape init failed', err);
            showMountError(mount, 'Mind map could not start. Try a hard refresh (Ctrl+F5).');
            return;
        }

        this.cy.on('tap', 'node', (evt) => this.onNodeTap(evt.target));
        this.runLayout(true);
    }

    buildElements() {
        const nodes = [];
        const edges = [];
        const p = this.payload;

        const addNode = (id, label, kind, extra = {}) => {
            nodes.push({ data: { id, label, kind, ...extra } });
        };
        const addEdge = (source, target) => {
            edges.push({ data: { id: `${source}->${target}`, source, target } });
        };

        addNode('root', p.root, 'root');

        (p.pillars ?? []).forEach((pillar) => {
            const id = `pillar:${pillar.id}`;
            addNode(id, pillar.label, 'pillar', {
                pillarId: pillar.id,
                expandable: pillar.expandable ? 'yes' : 'no',
                active: this.selectedPillar === pillar.id ? 'yes' : 'no',
            });
            addEdge('root', id);
        });

        const showTypeLines = (p.code_types ?? []).length > 0
            && (this.codeBranchOpen || this.selectedPillar === 'code_categories');

        if (showTypeLines) {
            (p.code_types ?? []).forEach((type) => {
                const id = `type:${type.key}`;
                addNode(id, `${type.title}\n(${type.code_count} codes)`, 'type', {
                    typeKey: type.key,
                    active: this.selectedTypeKey === type.key ? 'yes' : 'no',
                    warn: type.key === '_non_standard' ? 'yes' : 'no',
                });
                addEdge('pillar:code_categories', id);
            });
        }

        if (showTypeLines && this.selectedTypeKey) {
            const type = (p.code_types ?? []).find((t) => t.key === this.selectedTypeKey);
            (type?.sub_types ?? []).forEach((sub) => {
                const id = `sub:${this.selectedTypeKey}:${sub.key}`;
                addNode(id, `${sub.label}\n(${sub.code_count} codes)`, 'subtype', {
                    typeKey: this.selectedTypeKey,
                    subKey: sub.key,
                    active: this.selectedSubKey === sub.key ? 'yes' : 'no',
                });
                addEdge(`type:${this.selectedTypeKey}`, id);
            });
        }

        if (showTypeLines && this.selectedTypeKey && this.selectedSubKey) {
            const type = (p.code_types ?? []).find((t) => t.key === this.selectedTypeKey);
            const sub = type?.sub_types?.find((s) => s.key === this.selectedSubKey);
            const codes = sub?.codes ?? [];
            const maxCodes = 24;
            codes.slice(0, maxCodes).forEach((row, i) => {
                const id = `code:${row.code}:${i}`;
                const label = row.label ? `${row.code}\n${row.label}` : row.code;
                addNode(id, label, 'code');
                addEdge(`sub:${this.selectedTypeKey}:${this.selectedSubKey}`, id);
            });
            if (codes.length > maxCodes) {
                const moreId = `code:more:${this.selectedTypeKey}:${this.selectedSubKey}`;
                addNode(moreId, `+ ${codes.length - maxCodes} more codes\n(see legend list above)`, 'blurb');
                addEdge(`sub:${this.selectedTypeKey}:${this.selectedSubKey}`, moreId);
            }
        }

        if (this.pillarBlurbId) {
            const blurb = (p.pillars ?? []).find((x) => x.id === this.pillarBlurbId)?.blurb ?? '';
            if (blurb !== '') {
                addNode(`blurb:${this.pillarBlurbId}`, blurb, 'blurb');
                addEdge(`pillar:${this.pillarBlurbId}`, `blurb:${this.pillarBlurbId}`);
            }
        }

        return [...nodes, ...edges];
    }

    rebuild() {
        if (!this.cy) {
            return;
        }
        this.cy.json({ elements: this.buildElements() });
        this.runLayout(true);
    }

    runLayout(fit) {
        if (!this.cy || this.cy.nodes().length === 0) {
            return;
        }
        const layout = this.cy.layout({
            name: 'dagre',
            rankDir: 'LR',
            nodeSep: 48,
            edgeSep: 12,
            rankSep: 110,
            padding: 48,
            animate: true,
            animationDuration: 280,
        });
        layout.run();
        layout.one('layoutstop', () => {
            if (fit) {
                this.cy.fit(undefined, 56);
            }
        });
    }

    onNodeTap(node) {
        const kind = node.data('kind');
        const p = this.payload;

        if (kind === 'pillar') {
            const pillarId = node.data('pillarId');
            this.selectedPillar = pillarId;
            this.pillarBlurbId = null;

            if (node.data('expandable') === 'yes') {
                this.codeBranchOpen = true;
                if (!this.selectedTypeKey && p.code_types?.length) {
                    this.selectedTypeKey = p.code_types[0].key;
                    this.selectedSubKey = p.code_types[0].sub_types?.[0]?.key ?? null;
                }
            } else {
                this.codeBranchOpen = false;
                this.pillarBlurbId = pillarId;
                this.selectedTypeKey = null;
                this.selectedSubKey = null;
            }
            this.rebuild();
            return;
        }

        if (kind === 'type') {
            this.selectedPillar = 'code_categories';
            this.codeBranchOpen = true;
            this.selectedTypeKey = node.data('typeKey');
            this.selectedSubKey = p.code_types
                ?.find((t) => t.key === this.selectedTypeKey)?.sub_types?.[0]?.key ?? null;
            this.rebuild();
            return;
        }

        if (kind === 'subtype') {
            this.selectedSubKey = node.data('subKey');
            this.rebuild();
        }
    }
}

export function bootIndicatorMindmap() {
    document.querySelectorAll('[data-tk-indicator-mindmap]').forEach((host) => {
        if (host.dataset.tkMindmapReady === '1') {
            return;
        }
        const mount = host.querySelector('[data-tk-mindmap-mount]');
        if (!mount) {
            console.error('[tilkit mindmap] Missing [data-tk-mindmap-mount]');
            return;
        }
        const payload = readPayload(host);
        if (!payload) {
            showMountError(mount, 'Mind map data is missing. Reload the page or re-import the legend.');
            return;
        }
        host.dataset.tkMindmapReady = '1';
        new IndicatorMindmapGraph(mount, payload);
    });
}
