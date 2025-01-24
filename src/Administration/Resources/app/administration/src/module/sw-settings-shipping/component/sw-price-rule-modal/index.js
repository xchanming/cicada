import template from './sw-price-rule-modal.html.twig';

/**
 * @sw-package checkout
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    computed: {
        modalTitle() {
            return this.$tc('sw-settings-shipping.shippingPriceModal.modalTitle');
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');
        },
    },
};
