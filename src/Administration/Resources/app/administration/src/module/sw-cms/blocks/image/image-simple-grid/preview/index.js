import template from './sw-cms-preview-image-simple-grid.html.twig';
import './sw-cms-preview-image-simple-grid.scss';

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
