import template from './sw-cms-preview-text-on-image.html.twig';
import './sw-cms-preview-text-on-image.scss';

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
