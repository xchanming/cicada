import template from './sw-cms-el-preview-image-gallery.html.twig';
import './sw-cms-el-preview-image-gallery.scss';

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
