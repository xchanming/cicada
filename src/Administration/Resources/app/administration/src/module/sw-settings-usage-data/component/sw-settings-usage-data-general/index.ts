import template from './sw-settings-usage-data-general.html.twig';

/**
 * @package data-services
 *
 * @private
 */
export default Cicada.Component.wrapComponentConfig({
    name: 'sw-settings-usage-data-general',

    compatConfig: Cicada.compatConfig,

    template,

    inject: [
        'usageDataService',
    ],

    methods: {
        async createdComponent() {
            const consent = await this.usageDataService.getConsent();

            Cicada.State.commit('usageData/updateConsent', consent);
        },
    },

    created() {
        void this.createdComponent();
    },
});
