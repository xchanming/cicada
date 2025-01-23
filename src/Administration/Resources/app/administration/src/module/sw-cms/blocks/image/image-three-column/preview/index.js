import template from './sw-cms-preview-image-three-column.html.twig';
import './sw-cms-preview-image-three-column.scss';

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
