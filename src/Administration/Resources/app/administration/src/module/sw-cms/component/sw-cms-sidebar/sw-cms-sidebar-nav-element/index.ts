import { type PropType } from 'vue';
import template from './sw-cms-sidebar-nav-element.html.twig';
import './sw-cms-sidebar-nav-element.scss';

/**
 * @package discovery
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    emits: [
        'block-duplicate',
        'block-delete',
    ],

    props: {
        block: {
            type: Object as PropType<Entity<'cms_block'>>,
            required: true,
        },

        removable: {
            type: Boolean,
            required: false,
            default() {
                return false;
            },
        },

        duplicable: {
            type: Boolean,
            required: false,
            default() {
                return true;
            },
        },
    },

    methods: {
        onBlockDuplicate() {
            this.$emit('block-duplicate', this.block);
        },

        onBlockDelete() {
            this.$emit('block-delete', this.block);
        },
    },
});
