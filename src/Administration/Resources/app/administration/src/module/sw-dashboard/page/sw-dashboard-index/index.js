import template from './sw-dashboard-index.html.twig';
import './sw-dashboard-index.scss';

/**
 * @sw-package after-sales
 *
 * @private
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    data() {},

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            /* @deprecated tag:v6.7.0 - Will be removed, use API instead */
            Cicada.ExtensionAPI.publishData({
                id: 'sw-dashboard-detail__todayOrderData',
                path: 'todayOrderData',
                scope: this,
                deprecated: true,
                deprecationMessage: 'No replacement available, use API instead.',
            });
            /* @deprecated tag:v6.7.0 - Will be removed, use API instead */
            Cicada.ExtensionAPI.publishData({
                id: 'sw-dashboard-detail__statisticDateRanges',
                path: 'statisticDateRanges',
                scope: this,
                deprecated: true,
                deprecationMessage: 'No replacement available, use API instead.',
            });
        },
    },
});
