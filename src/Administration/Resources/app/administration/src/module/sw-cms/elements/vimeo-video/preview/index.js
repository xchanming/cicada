import template from './sw-cms-el-preview-vimeo-video.html.twig';
import './sw-cms-el-preview-vimeo-video.scss';

/**
 * @private
 * @package discovery
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
