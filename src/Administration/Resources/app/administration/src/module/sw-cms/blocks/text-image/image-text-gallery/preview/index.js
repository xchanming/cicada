import template from './sw-cms-preview-image-text-gallery.html.twig';
import './sw-cms-preview-image-text-gallery.scss';

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
