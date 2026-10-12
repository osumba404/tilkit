import Sortable from 'sortablejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('formBuilder', (initial, typeCatalog) => ({
        fields: initial.length ? initial.map(normalizeField) : [],
        typeCatalog,
        typeMenuOpen: null,
        previewPage: 0,

        get previewSectionCount() {
            let sections = 1;
            this.fields.forEach((f) => {
                if (
                    f.field_type === 'group'
                    || f.field_type === 'repeat'
                    || (f.field_type === 'note' && String(f.name).startsWith('section_'))
                ) {
                    sections += 1;
                }
            });
            return sections;
        },

        init() {
            this.$nextTick(() => {
                const el = this.$refs.fieldList;
                if (!el) {
                    return;
                }
                Sortable.create(el, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: () => this.syncOrderFromDom(),
                });
            });
        },

        syncOrderFromDom() {
            const names = [...this.$refs.fieldList.querySelectorAll('[data-field-key]')].map(
                (node) => node.dataset.fieldKey,
            );
            this.fields = names
                .map((key) => this.fields.find((f) => f._key === key))
                .filter(Boolean);
        },

        addField(type = 'text') {
            const n = this.fields.length + 1;
            const label = 'Question ' + n;
            this.fields.push(
                normalizeField({
                    _key: crypto.randomUUID(),
                    name: slugFieldName(label, n),
                    autoName: true,
                    field_type: type,
                    label,
                    hint: '',
                    is_required: false,
                    parameters: defaultParameters(type),
                }),
            );
        },

        removeField(index) {
            this.fields.splice(index, 1);
        },

        syncAutoName(field, index) {
            if (!field.autoName) {
                return;
            }
            field.name = slugFieldName(field.label, index + 1);
        },

        ensureFieldNames() {
            const used = new Set();
            this.fields.forEach((field, index) => {
                if (field.autoName) {
                    field.name = slugFieldName(field.label, index + 1);
                }
                field.name = uniqueFieldName(field.name, used, index + 1);
                used.add(field.name);

                if (this.supportsChoices(field) && field.parameters?.choices) {
                    const choiceUsed = new Set();
                    field.parameters.choices = field.parameters.choices.filter(
                        (choice) => (choice.label ?? '').trim() !== '',
                    );
                    field.parameters.choices.forEach((choice, ci) => {
                        choice.value = uniqueFieldName(
                            slugFieldName(choice.label, ci + 1),
                            choiceUsed,
                            ci + 1,
                        );
                        choiceUsed.add(choice.value);
                    });
                }
            });
        },

        typeMeta(code) {
            return this.typeCatalog.find((t) => t.code === code) ?? {
                code,
                label: code.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
                icon: 'M4 12h16',
            };
        },

        supportsChoices(field) {
            return ['select_one', 'select_multiple', 'rank'].includes(field.field_type);
        },

        addChoice(field) {
            if (!field.parameters.choices) {
                field.parameters.choices = [];
            }
            field.parameters.choices.push({ value: '', label: '' });
        },

        removeChoice(field, index) {
            field.parameters.choices.splice(index, 1);
        },

        toggleTypeMenu(index) {
            this.typeMenuOpen = this.typeMenuOpen === index ? null : index;
        },

        setType(field, code) {
            field.field_type = code;
            field.parameters = defaultParameters(code, field.parameters);
            this.typeMenuOpen = null;
        },
    }));
});

function normalizeField(field) {
    const type = field.field_type ?? 'text';
    const name = field.name ?? 'field_1';

    return {
        _key: field._key ?? crypto.randomUUID(),
        name,
        autoName: field.autoName ?? false,
        field_type: type,
        label: field.label ?? '',
        hint: field.hint ?? '',
        is_required: Boolean(field.is_required),
        relevant_expr: field.relevant_expr ?? '',
        repeat_parent: field.repeat_parent ?? '',
        parameters: {
            ...defaultParameters(type),
            ...(field.parameters ?? {}),
        },
    };
}

function slugFieldName(label, fallbackIndex) {
    let slug = (label ?? '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');

    if (!slug || !/^[a-z]/.test(slug)) {
        slug = 'field_' + fallbackIndex;
    }

    return slug.slice(0, 80);
}

function uniqueFieldName(base, used, fallbackIndex) {
    let name = base || 'field_' + fallbackIndex;
    if (!used.has(name)) {
        return name;
    }
    let suffix = 2;
    while (used.has(name + '_' + suffix)) {
        suffix++;
    }
    return (name.slice(0, 76) + '_' + suffix).slice(0, 80);
}

function defaultParameters(type, existing = {}) {
    const base = { ...existing };
    if (['select_one', 'select_multiple', 'rank'].includes(type)) {
        if (!Array.isArray(base.choices)) {
            base.choices = [];
        }
        if (type === 'select_multiple' && base.max_selections == null) {
            base.max_selections = 3;
        }
    }
    if (type === 'integer' || type === 'decimal' || type === 'range') {
        base.min = base.min ?? null;
        base.max = base.max ?? null;
    }
    if (['image', 'audio', 'video', 'file'].includes(type)) {
        base.allow_capture = base.allow_capture ?? true;
        base.allow_upload = base.allow_upload ?? true;
        base.max_size_mb = base.max_size_mb ?? 25;
    }
    if (type === 'signature') {
        base.allow_draw = base.allow_draw ?? true;
        base.allow_upload = base.allow_upload ?? true;
        base.allow_capture = base.allow_capture ?? true;
        base.max_size_mb = base.max_size_mb ?? 10;
    }
    if (type === 'calculate') {
        base.expression = base.expression ?? '';
    }
    return base;
}
