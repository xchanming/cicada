import template from './sw-cms-block-product-heading.html.twig';
import './sw-cms-block-product-heading.scss';

const { Store } = Cicada;

/**
 * @private
 * @sw-package discovery
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    computed: {
        currentDeviceView() {
            return Store.get('cmsPage').currentCmsDeviceView;
        },

        currentDeviceViewClass() {
            return `is--${this.currentDeviceView}`;
        },
    },
};
