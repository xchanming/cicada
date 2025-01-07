import template from './sw-cms-preview-image.html.twig';
import './sw-cms-preview-image.scss';

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
