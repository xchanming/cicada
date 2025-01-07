import template from './sw-cms-preview-image-four-column.html.twig';
import './sw-cms-preview-image-four-column.scss';

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
