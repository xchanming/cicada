import template from './sw-cms-preview-image-text-row.html.twig';
import './sw-cms-preview-image-text-row.scss';

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
