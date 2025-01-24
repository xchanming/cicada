/**
 * @sw-package fundamentals@after-sales
 */
import template from './sw-import-export-view-export.html.twig';
import './sw-import-export-view-export.scss';

/**
 * @private
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    methods: {
        reloadContent(log) {
            this.$refs.activityGrid.addActivity(log);
            this.$refs.activityGrid.fetchActivities();
        },
    },
};
