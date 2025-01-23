import template from './sw-cms-preview-vimeo-video.html.twig';
import './sw-cms-preview-vimeo-video.scss';

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
