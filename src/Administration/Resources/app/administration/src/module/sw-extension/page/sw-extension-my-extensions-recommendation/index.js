import template from './sw-extension-store-recommendation.html.twig';

/**
 * @sw-package checkout
 * @private
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    data() {
        return {
            isLoading: true,
        };
    },

    methods: {
        finishLoading() {
            this.isLoading = false;
        },
    },
};
