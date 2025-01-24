import { type PropType } from 'vue';
import template from './sw-cms-block-layout-config.html.twig';
import './sw-cms-block-layout-config.scss';

/**
 * @private
 * @sw-package discovery
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        block: {
            type: Object as PropType<Entity<'cms_block'>>,
            required: true,
        },
    },
});
