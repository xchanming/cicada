import template from './sw-cms-preview-gallery-buybox.html.twig';
import './sw-cms-preview-gallery-buybox.scss';

/**
 * @private
 * @package buyers-experience
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
