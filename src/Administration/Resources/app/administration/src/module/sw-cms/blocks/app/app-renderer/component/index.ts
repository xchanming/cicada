import type { PropType } from 'vue';
import template from './sw-cms-block-app-renderer.html.twig';

/**
 * @private
 * @sw-package discovery
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        block: {
            type: Object as PropType<{
                slots: Array<{
                    slot: string;
                    type: string;
                }>;
                customFields?: {
                    slotLayout?: {
                        grid?: string;
                    };
                };
            }>,
            required: false,
            default() {
                return {};
            },
        },
    },

    computed: {
        slots() {
            return this.block.slots ?? [];
        },

        blockStyle() {
            return {
                display: 'grid',
                grid: this.block?.customFields?.slotLayout?.grid ?? 'auto / auto',
            };
        },
    },
});
