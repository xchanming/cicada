import template from './sw-customer-detail-base.html.twig';

/**
 * @sw-package checkout
 */

const { Criteria } = Cicada.Data;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['repositoryFactory'],

    props: {
        customer: {
            type: Object,
            required: true,
        },

        customerEditMode: {
            type: Boolean,
            required: true,
            default: false,
        },

        isLoading: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data() {
        return {
            customerCustomFieldSets: null,
        };
    },

    computed: {
        customFieldSetRepository() {
            return this.repositoryFactory.create('custom_field_set');
        },

        customFieldSetCriteria() {
            const criteria = new Criteria(1, 25);

            criteria.addFilter(Criteria.equals('relations.entityName', 'customer'));
            criteria.getAssociation('customFields').addSorting(Criteria.naturalSorting('config.customFieldPosition'));

            return criteria;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            Cicada.State.commit('cicadaApps/setSelectedIds', this.customer.id ? [this.customer.id] : []);

            this.customFieldSetRepository.search(this.customFieldSetCriteria).then((customFieldSets) => {
                this.customerCustomFieldSets = customFieldSets;
            });
        },
    },
};
