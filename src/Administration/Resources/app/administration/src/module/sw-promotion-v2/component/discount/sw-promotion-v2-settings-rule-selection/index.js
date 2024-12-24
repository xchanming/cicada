/**
 * @package buyers-experience
 */
import template from './sw-promotion-v2-settings-rule-selection.html.twig';

const { Criteria } = Cicada.Data;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'acl',
    ],

    props: {
        discount: {
            type: Object,
            required: true,
        },
    },

    computed: {
        ruleCriteria() {
            return new Criteria(1, 25).addSorting(Criteria.sort('name', 'ASC', false));
        },
    },
};
