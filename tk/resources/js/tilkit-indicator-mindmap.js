import cytoscape from 'cytoscape';

function readPayload(host) {
    const el = host.querySelector('[data-tk-mindmap-json]');
    if (!el?.textContent) {
        return null;
    }
    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
}

const CY_STYLES = [
    {
        selector: 'node',
        style: {
            label: 'data(label)',
            'text-wrap': 'wrap',
            'text-max-width': '200px',
            'font-size': '11px',
            'font-family': 'inherit',
            color: '#f8fafc',
            'text-valign': 'center',
            'text-halign': 'center',
            'overlay-padding': '6px',
            shape: 'round-rectangle',
            width: 'label',
            height: 'label',
            padding: '10px',
        },
    },
    {
        selector: 'node[kind = "root"]',
        style: {
            'background-color': '#6d28d9',
            'border-width': 2,
            'border-color': '#a78bfa',
            'font-weight': 'bold',
            'font-size': '12px',
        },
    },
    {
        selector: 'node[kind = "pillar"]',
        style: {
            'background-color': '#334155',
            'border-width': 1,
            'border-color': '#64748b',
        },
    },
    {
        selector: 'node[kind = "pillar"][active = "yes"]',
        style: {
            'border-width': 3,
            'border-color': '#38bdf8',
        },
    },
    {
        selector: 'node[kind = "type"]',
        style: {
            'background-color': '#065f46',
            'border-width': 1,
            'border-color': '#10b981',
        },
    },
    {
        selector: 'node[kind = "type"][active = "yes"]',
        style: {
            'border-width': 3,
            'border-color': '#6ee7b7',
        },
    },
    {
        selector: 'node[kind = "subtype"]',
        style: {
            'background-color': '#134e4a',
            'border-width': 1,
            'border-color': '#14b8a6',
            'font-size': '10px',
        },
    },
    {
        selector: 'node[kind = "subtype"][active = "yes"]',
        style: {
            'border-width': 3,
            'border-color': '#5eead4',
        },
    },
    {
        selector: 'node[kind = "code"]',
        style: {
            'background-color': '#022c22',
            'border-width': 1,
            'border-color': '#047857',
            'font-family': 'ui-monospace, monospace',
            'font-size': '9px',
        },
    },
    {
        selector: 'node[kind = "blurb"]',
        style: {
            'background-color': '#1e293b',
            'border-width': 1,
            'border-color': '#94a3b8',
            'font-size': '10px',
            'text-max-width': '240px',
        },
    },
    {
        selector: 'node[warn = "yes"]',
        style: {
            'background-color': '#451a03',
            'border-color': '#f59e0b',
        },
    },
    {
        selector: 'edge',
        style: {
            width: 2.5,
            'line-color': '#38bdf8',
            'curve-style': 'bezier',
            opacity: 0.85,
            'target-arrow-shape': 'none',
        },
    },
];

class IndicatorMindmapGraph {
    constructor(mount, payload) {
        this.mount = mount;
        this.payload = payload;
        this.codeBranchOpen = true;
        this.selectedPillar = 'code_categories';
        this.selectedTypeKey = payload.code_types?.[0]?.key ?? null;
        this.selectedSubKey = payload.code_types?.[0]?.sub_types?.[0]?.key ?? null;
        this.pillarBlurbId = null;

        this.cy = cytoscape({
            container: mount,
            elements: this.buildElements(),
            style: CY_STYLES,
            wheelSensitivity: 0.25,
            minZoom: 0.35,
            maxZoom: 2.5,
            boxSelectionEnabled: false,
        });

        this.cy.on('tap', 'node', (evt) => this.onNodeTap(evt.target));
        this.runLayout();
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
                blurb: pillar.blurb ?? '',
                active: this.selectedPillar === pillar.id ? 'yes' : 'no',
            });
            addEdge('root', id);
        });

        if (this.codeBranchOpen && this.selectedPillar === 'code_categories') {
            (p.code_types ?? []).forEach((type) => {
                const id = `type:${type.key}`;
                addNode(id, `${type.title}\n${type.code_count} codes`, 'type', {
                    typeKey: type.key,
                    active: this.selectedTypeKey === type.key ? 'yes' : 'no',
                    warn: type.key === '_non_standard' ? 'yes' : 'no',
                });
                addEdge('pillar:code_categories', id);
            });
        }

        if (this.codeBranchOpen && this.selectedTypeKey) {
            const type = (p.code_types ?? []).find((t) => t.key === this.selectedTypeKey);
            (type?.sub_types ?? []).forEach((sub) => {
                const id = `sub:${this.selectedTypeKey}:${sub.key}`;
                addNode(id, `${sub.label}\n${sub.code_count} code(s)`, 'subtype', {
                    typeKey: this.selectedTypeKey,
                    subKey: sub.key,
                    active: this.selectedSubKey === sub.key ? 'yes' : 'no',
                });
                addEdge(`type:${this.selectedTypeKey}`, id);
            });
        }

        if (this.codeBranchOpen && this.selectedTypeKey && this.selectedSubKey) {
            const type = (p.code_types ?? []).find((t) => t.key === this.selectedTypeKey);
            const sub = type?.sub_types?.find((s) => s.key === this.selectedSubKey);
            (sub?.codes ?? []).forEach((row, i) => {
                const id = `code:${row.code}:${i}`;
                const label = row.label ? `${row.code}\n${row.label}` : row.code;
                addNode(id, label, 'code');
                addEdge(`sub:${this.selectedTypeKey}:${this.selectedSubKey}`, id);
            });
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

    preservedPositions() {
        const pos = {};
        this.cy.nodes().forEach((n) => {
            pos[n.id()] = { ...n.position() };
        });
        return pos;
    }

    rebuild() {
        const saved = this.preservedPositions();
        this.cy.json({ elements: this.buildElements() });
        this.cy.nodes().forEach((n) => {
            const p = saved[n.id()];
            if (p) {
                n.position(p);
            }
        });
        const needsLayout = this.cy.nodes().filter((n) => !saved[n.id()]);
        if (needsLayout.length > 0) {
            this.runLayout(needsLayout);
        } else {
            this.cy.fit(undefined, 48);
        }
    }

    runLayout(eles = null) {
        const collection = eles ?? this.cy.elements();
        collection.layout({
            name: 'breadthfirst',
            directed: true,
            roots: '#root',
            padding: 40,
            spacingFactor: 1.35,
            avoidOverlap: true,
            nodeDimensionsIncludeLabels: true,
        }).run();
        this.cy.fit(undefined, 48);
    }

    onNodeTap(node) {
        const kind = node.data('kind');
        const p = this.payload;

        if (kind === 'pillar') {
            const pillarId = node.data('pillarId');
            this.selectedPillar = pillarId;
            if (node.data('expandable') === 'yes') {
                this.codeBranchOpen = true;
                this.pillarBlurbId = null;
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
            this.pillarBlurbId = null;
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
        const payload = readPayload(host);
        if (!payload) {
            return;
        }
        const mount = host.querySelector('[data-tk-mindmap-mount]');
        if (!mount) {
            return;
        }
        host.dataset.tkMindmapReady = '1';
        new IndicatorMindmapGraph(mount, payload);
    });
}
