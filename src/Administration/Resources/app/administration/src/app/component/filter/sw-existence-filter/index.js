import template from './sw-existence-filter.html.twig';

const { Component } = Cicada;
const { Criteria } = Cicada.Data;

/**
 * @private
 */
Component.register('sw-existence-filter', {
    template,

    compatConfig: Cicada.compatConfig,

    emits: [
        'filter-update',
        'filter-reset',
    ],

    props: {
        filter: {
            type: Object,
            required: true,
        },
        active: {
            type: Boolean,
            required: true,
        },
    },

    computed: {
        value() {
            return this.filter.value;
        },
    },

    methods: {
        changeValue(newValue) {
            if (!newValue) {
                this.resetFilter();
                return;
            }

            const fieldName = this.filter.property.concat(this.filter.schema ? `.${this.filter.schema.localField}` : '');

            let filterCriteria = [Criteria.equals(fieldName, null)];

            if (newValue === 'true') {
                filterCriteria = [Criteria.not('AND', filterCriteria)];
            }

            this.$emit('filter-update', this.filter.name, filterCriteria, newValue);
        },

        resetFilter() {
            this.$emit('filter-reset', this.filter.name);
        },
    },
});
