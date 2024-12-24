import template from './sw-cms-preview-image-text-cover.html.twig';
import './sw-cms-preview-image-text-cover.scss';

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
