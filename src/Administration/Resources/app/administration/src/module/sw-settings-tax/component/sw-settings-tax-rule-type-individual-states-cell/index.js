import template from './sw-settings-tax-rule-type-individual-states-cell.html.twig';

/**
 * @sw-package checkout
 */

const { Context } = Cicada;
const { Criteria } = Cicada.Data;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['repositoryFactory'],

    props: {
        taxRule: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            individualStates: null,
        };
    },

    computed: {
        stateRepository() {
            return this.repositoryFactory.create('country_state');
        },
    },

    watch: {
        'taxRule.data.states'() {
            this.loadStates();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadStates();
        },
        loadStates() {
            if (!this.taxRule.data || !this.taxRule.data.states || !this.taxRule.data.states.length) {
                this.individualStates = [];
                return;
            }

            const criteria = new Criteria(1, 25);
            criteria.setIds(this.taxRule.data.states);

            this.stateRepository.search(criteria, Context.api).then((states) => {
                this.individualStates = states.map((state) => state.name);
            });
        },
    },
};
