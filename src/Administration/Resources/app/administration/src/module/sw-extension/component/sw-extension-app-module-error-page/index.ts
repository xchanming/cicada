import template from './sw-extension-app-module-error-page.html.twig';
import './sw-extension-app-module-error-page.scss';

/**
 * @sw-package checkout
 * @private
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    computed: {
        assetFilter() {
            return Cicada.Filter.getByName('asset');
        },
    },

    methods: {
        goBack(): void {
            this.$router.go(-1);
        },
    },
});
