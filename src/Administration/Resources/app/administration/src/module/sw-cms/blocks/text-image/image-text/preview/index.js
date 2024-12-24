import template from './sw-cms-preview-image-text.html.twig';
import './sw-cms-preview-image-text.scss';

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
