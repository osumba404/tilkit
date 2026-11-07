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
        selector: 'node[kind = "category"]',
        style: {
            'background-color': '#1d4ed8',
            'border-width': 2,
            'border-color': '#93c5fd',
        },
    },
    {
        selector: 'node[kind = "category"][active = "yes"]',
        style: {
            'border-color': '#bfdbfe',
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

const MINDMAP_EXPORT_BG = '#0f172a';

class IndicatorMindmapGraph {
    constructor(mount, payload) {
        this.mount = mount;
        this.payload = payload;
        this.codeBranchOpen = true;
        this.selectedPillar = 'code_categories';
        this.selectedCategoryKey = null;
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

    resetHierarchyBranch() {
        this.selectedCategoryKey = null;
        this.selectedTypeKey = null;
        this.selectedSubKey = null;
    }

    codeLabel(row) {
        const classification = row.classification || row.label || '';
        const lines = [row.code];
        if (classification) {
            lines.push(classification);
        }
        if (row.behaviour_count > 0) {
            lines.push(`${row.behaviour_count} D options`);
            if (row.sample_behaviour) {
                lines.push(`e.g. ${row.sample_behaviour}`);
            }
        }
        return lines.join('\n');
    }

    activeCategory() {
        return (this.payload.categories ?? []).find((c) => c.key === this.selectedCategoryKey) ?? null;
    }

    activeType() {
        const cat = this.activeCategory();
        return cat?.types?.find((t) => t.key === this.selectedTypeKey) ?? null;
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

        const hasHierarchy = (p.categories ?? []).length > 0 || (p.code_types ?? []).length > 0;
        const showTypeLines = hasHierarchy
            && (this.codeBranchOpen || this.selectedPillar === 'code_categories');

        if (showTypeLines && (p.categories ?? []).length > 0) {
            const categories = p.categories;
            if (!this.selectedCategoryKey) {
                categories.forEach((cat) => {
                    const id = `cat:${cat.key}`;
                    addNode(id, `L1 · ${cat.key}\n${cat.label}\n(${cat.code_count} codes)`, 'category', {
                        categoryKey: cat.key,
                    });
                    addEdge('pillar:code_categories', id);
                });
            } else {
                const cat = categories.find((c) => c.key === this.selectedCategoryKey);
                if (cat) {
                    const catId = `cat:${cat.key}`;
                    addNode(catId, `L1 · ${cat.key}\n${cat.label}\n(${cat.code_count} codes)`, 'category', {
                        categoryKey: cat.key,
                        active: 'yes',
                    });
                    addEdge('pillar:code_categories', catId);

                    if (!this.selectedTypeKey) {
                        (cat.types ?? []).forEach((type) => {
                            const id = `type:${type.key}`;
                            addNode(id, `L2 · ${type.title}\n(${type.code_count} codes)`, 'type', {
                                typeKey: type.key,
                                categoryKey: cat.key,
                            });
                            addEdge(catId, id);
                        });
                    } else {
                        const type = (cat.types ?? []).find((t) => t.key === this.selectedTypeKey);
                        if (type) {
                            const typeId = `type:${type.key}`;
                            addNode(typeId, `L2 · ${type.title}\n(${type.code_count} codes)`, 'type', {
                                typeKey: type.key,
                                categoryKey: cat.key,
                                active: 'yes',
                                warn: type.key === '_non_standard' ? 'yes' : 'no',
                            });
                            addEdge(catId, typeId);

                            (type.sub_types ?? []).forEach((sub) => {
                                const subId = `sub:${type.key}:${sub.key}`;
                                addNode(subId, `L3 · ${sub.label}\n(${sub.code_count} codes)`, 'subtype', {
                                    typeKey: type.key,
                                    subKey: sub.key,
                                    active: this.selectedSubKey === sub.key ? 'yes' : 'no',
                                });
                                addEdge(typeId, subId);
                            });

                            if (this.selectedSubKey) {
                                const sub = type.sub_types?.find((s) => s.key === this.selectedSubKey);
                                const codes = sub?.codes ?? [];
                                const maxCodes = 20;
                                codes.slice(0, maxCodes).forEach((row, i) => {
                                    const id = `code:${row.code}:${i}`;
                                    addNode(id, this.codeLabel(row), 'code');
                                    addEdge(`sub:${type.key}:${this.selectedSubKey}`, id);
                                });
                                if (codes.length > maxCodes) {
                                    const moreId = `code:more:${type.key}:${this.selectedSubKey}`;
                                    addNode(
                                        moreId,
                                        `+ ${codes.length - maxCodes} more codes\n(open dictionary / legend below)`,
                                        'blurb',
                                    );
                                    addEdge(`sub:${type.key}:${this.selectedSubKey}`, moreId);
                                }
                            }
                        }
                    }
                }
            }
        } else if (showTypeLines) {
            (p.code_types ?? []).forEach((type) => {
                const id = `type:${type.key}`;
                addNode(id, `${type.title}\n(${type.code_count} codes)`, 'type', {
                    typeKey: type.key,
                    active: this.selectedTypeKey === type.key ? 'yes' : 'no',
                    warn: type.key === '_non_standard' ? 'yes' : 'no',
                });
                addEdge('pillar:code_categories', id);
            });

            if (this.selectedTypeKey) {
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

            if (this.selectedTypeKey && this.selectedSubKey) {
                const type = (p.code_types ?? []).find((t) => t.key === this.selectedTypeKey);
                const sub = type?.sub_types?.find((s) => s.key === this.selectedSubKey);
                const codes = sub?.codes ?? [];
                const maxCodes = 20;
                codes.slice(0, maxCodes).forEach((row, i) => {
                    const id = `code:${row.code}:${i}`;
                    addNode(id, this.codeLabel(row), 'code');
                    addEdge(`sub:${this.selectedTypeKey}:${this.selectedSubKey}`, id);
                });
                if (codes.length > maxCodes) {
                    const moreId = `code:more:${this.selectedTypeKey}:${this.selectedSubKey}`;
                    addNode(moreId, `+ ${codes.length - maxCodes} more codes\n(open dictionary / legend below)`, 'blurb');
                    addEdge(`sub:${this.selectedTypeKey}:${this.selectedSubKey}`, moreId);
                }
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

    resizeAndFit() {
        if (!this.cy) {
            return;
        }
        const apply = () => {
            const host = this.mount.parentElement;
            const hostHeight = host?.clientHeight ?? 0;
            if (hostHeight > 0) {
                this.mount.style.height = `${hostHeight}px`;
            }
            this.cy.resize();
            if (this.cy.nodes().length > 0) {
                this.cy.fit(undefined, 56);
            }
        };
        apply();
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                apply();
                window.setTimeout(apply, 80);
                window.setTimeout(apply, 250);
            });
        });
    }

    async exportPngBlob() {
        if (!this.cy || this.cy.nodes().length === 0) {
            return null;
        }
        const opts = {
            full: true,
            bg: MINDMAP_EXPORT_BG,
            scale: 2,
        };
        try {
            const out = this.cy.png({ ...opts, output: 'blob-promise' });
            if (out && typeof out.then === 'function') {
                return await out;
            }
        } catch {
            /* fall through */
        }
        const dataUri = this.cy.png({ ...opts, output: 'base64uri' });
        const response = await fetch(dataUri);

        return response.blob();
    }

    async copyImageToClipboard() {
        const blob = await this.exportPngBlob();
        if (!blob) {
            return false;
        }
        if (!navigator.clipboard?.write || typeof ClipboardItem === 'undefined') {
            return false;
        }
        await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);

        return true;
    }

    async downloadPng() {
        const blob = await this.exportPngBlob();
        if (!blob) {
            return false;
        }
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `tilkit-indicator-mindmap-${new Date().toISOString().slice(0, 10)}.png`;
        link.click();
        URL.revokeObjectURL(url);

        return true;
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
                this.resetHierarchyBranch();
            } else {
                this.codeBranchOpen = false;
                this.pillarBlurbId = pillarId;
                this.selectedCategoryKey = null;
                this.selectedTypeKey = null;
                this.selectedSubKey = null;
            }
            this.rebuild();
            return;
        }

        if (kind === 'category') {
            this.selectedPillar = 'code_categories';
            this.codeBranchOpen = true;
            const tapped = node.data('categoryKey');
            if (
                tapped === this.selectedCategoryKey
                && !this.selectedTypeKey
                && !this.selectedSubKey
            ) {
                this.resetHierarchyBranch();
            } else {
                this.selectedCategoryKey = tapped;
                this.selectedTypeKey = null;
                this.selectedSubKey = null;
            }
            this.rebuild();
            return;
        }

        if (kind === 'type') {
            this.selectedPillar = 'code_categories';
            this.codeBranchOpen = true;
            if (node.data('categoryKey')) {
                this.selectedCategoryKey = node.data('categoryKey');
            }
            const nextType = node.data('typeKey');
            if (nextType === this.selectedTypeKey) {
                this.selectedTypeKey = null;
                this.selectedSubKey = null;
            } else {
                this.selectedTypeKey = nextType;
                this.selectedSubKey = null;
            }
            this.rebuild();
            return;
        }

        if (kind === 'subtype') {
            this.selectedSubKey = node.data('subKey');
            this.rebuild();
        }
    }
}

function bindMindmapPanelControls(panel, graph) {
    const statusEl = panel.querySelector('[data-tk-mindmap-status]');
    const fullscreenBtn = panel.querySelector('[data-tk-mindmap-fullscreen]');
    const copyBtn = panel.querySelector('[data-tk-mindmap-copy-image]');
    const downloadBtn = panel.querySelector('[data-tk-mindmap-download-image]');

    const setStatus = (message, isError = false) => {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = message;
        statusEl.classList.toggle('text-amber-800', isError);
        statusEl.classList.toggle('text-tk-muted', !isError);
        if (message && !isError) {
            window.setTimeout(() => {
                if (statusEl.textContent === message) {
                    statusEl.textContent = '';
                }
            }, 4000);
        }
    };

    const syncFullscreenUi = () => {
        const native = document.fullscreenElement === panel;
        const overlay = panel.classList.contains('tk-mindmap-panel--overlay');
        const active = native || overlay;
        if (fullscreenBtn) {
            fullscreenBtn.textContent = active ? 'Exit full screen' : 'Full screen';
            fullscreenBtn.setAttribute('aria-pressed', active ? 'true' : 'false');
        }
        document.body.classList.toggle('tk-mindmap-fs-open', active);
        if (!active) {
            graph.mount.style.height = '';
        }
        graph.resizeAndFit();
    };

    const mountLooksBlank = () => (graph.mount?.clientHeight ?? 0) < 80;

    const enterFullscreen = async () => {
        graph.resizeAndFit();
        let usedNative = false;
        try {
            if (panel.requestFullscreen) {
                await panel.requestFullscreen();
                usedNative = document.fullscreenElement === panel;
            }
        } catch {
            usedNative = false;
        }

        await new Promise((resolve) => {
            window.setTimeout(resolve, 120);
        });
        graph.resizeAndFit();

        if (!usedNative || mountLooksBlank()) {
            if (document.fullscreenElement === panel) {
                await document.exitFullscreen();
            }
            panel.classList.add('tk-mindmap-panel--overlay');
        }

        syncFullscreenUi();
    };

    const exitFullscreen = async () => {
        if (document.fullscreenElement === panel) {
            await document.exitFullscreen();
        }
        panel.classList.remove('tk-mindmap-panel--overlay');
        syncFullscreenUi();
    };

    fullscreenBtn?.addEventListener('click', async () => {
        const active = document.fullscreenElement === panel || panel.classList.contains('tk-mindmap-panel--overlay');
        if (active) {
            await exitFullscreen();
        } else {
            await enterFullscreen();
        }
    });

    document.addEventListener('fullscreenchange', () => {
        if (document.fullscreenElement === panel || panel.classList.contains('tk-mindmap-panel--overlay')) {
            syncFullscreenUi();
        } else if (!document.fullscreenElement) {
            panel.classList.remove('tk-mindmap-panel--overlay');
            syncFullscreenUi();
        }
    });

    window.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        if (document.fullscreenElement === panel || panel.classList.contains('tk-mindmap-panel--overlay')) {
            exitFullscreen();
        }
    });

    copyBtn?.addEventListener('click', async () => {
        copyBtn.disabled = true;
        try {
            const ok = await graph.copyImageToClipboard();
            setStatus(
                ok ? 'Graph copied to clipboard — paste into docs or chat.' : 'Copy failed (try Download PNG or allow clipboard access).',
                !ok,
            );
        } catch {
            setStatus('Copy failed — use Download PNG instead.', true);
        } finally {
            copyBtn.disabled = false;
        }
    });

    downloadBtn?.addEventListener('click', async () => {
        downloadBtn.disabled = true;
        try {
            const ok = await graph.downloadPng();
            setStatus(ok ? 'PNG downloaded.' : 'Nothing to export — expand the legend branch first.', !ok);
        } catch {
            setStatus('Download failed.', true);
        } finally {
            downloadBtn.disabled = false;
        }
    });

    if (typeof ResizeObserver !== 'undefined') {
        const ro = new ResizeObserver(() => graph.resizeAndFit());
        ro.observe(panel);
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
        const graph = new IndicatorMindmapGraph(mount, payload);
        const panel = host.closest('[data-tk-mindmap-panel]');
        if (panel) {
            bindMindmapPanelControls(panel, graph);
        }
    });
}
