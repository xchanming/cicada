import template from './sw-cms-el-preview-cross-selling.html.twig';
import './sw-cms-el-preview-cross-selling.scss';

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
