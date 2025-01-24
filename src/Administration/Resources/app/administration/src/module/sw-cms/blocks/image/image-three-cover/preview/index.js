import template from './sw-cms-preview-image-three-cover.html.twig';
import './sw-cms-preview-image-three-cover.scss';

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
