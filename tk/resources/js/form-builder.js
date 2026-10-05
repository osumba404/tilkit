import Sortable from 'sortablejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('formBuilder', (initial, typeCatalog) => ({
        fields: initial.length ? initial.map(normalizeField) : [],
        typeCatalog,
        typeMenuOpen: null,

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
            this.fields.push(
                normalizeField({
                    _key: crypto.randomUUID(),
                    name: 'field_' + n,
                    field_type: type,
                    label: 'Question ' + n,
                    hint: '',
                    is_required: false,
                    parameters: defaultParameters(type),
                }),
            );
        },

        removeField(index) {
            this.fields.splice(index, 1);
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
            const i = field.parameters.choices.length + 1;
            field.parameters.choices.push({ value: 'option_' + i, label: 'Option ' + i });
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

        parametersJson(field) {
            return JSON.stringify(field.parameters ?? {});
        },
    }));
});

function normalizeField(field) {
    const type = field.field_type ?? 'text';
    return {
        _key: field._key ?? crypto.randomUUID(),
        name: field.name ?? 'field_1',
        field_type: type,
        label: field.label ?? '',
        hint: field.hint ?? '',
        is_required: Boolean(field.is_required),
        parameters: {
            ...defaultParameters(type),
            ...(field.parameters ?? {}),
        },
    };
}

function defaultParameters(type, existing = {}) {
    const base = { ...existing };
    if (['select_one', 'select_multiple', 'rank'].includes(type)) {
        base.choices = base.choices?.length
            ? base.choices
            : [
                  { value: 'option_1', label: 'Option 1' },
                  { value: 'option_2', label: 'Option 2' },
              ];
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
    return base;
}
