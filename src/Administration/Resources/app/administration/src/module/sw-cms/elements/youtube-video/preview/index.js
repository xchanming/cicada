import template from './sw-cms-el-preview-youtube-video.html.twig';
import './sw-cms-el-preview-youtube-video.scss';

/**
 * @private
 * @sw-package discovery
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    computed: {
        assetFilter() {
            return Cicada.Filter.getByName('asset');
        },
    },
};
