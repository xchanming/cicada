import template from './sw-cms-preview-image-two-column.html.twig';
import './sw-cms-preview-image-two-column.scss';

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
