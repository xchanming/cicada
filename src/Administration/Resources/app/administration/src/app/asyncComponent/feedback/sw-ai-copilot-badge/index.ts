import template from './sw-ai-copilot-badge.html.twig';
import './sw-ai-copilot-badge.scss';

/**
 * @sw-package framework
 * @private
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        label: {
            type: Boolean,
            required: false,
            default: true,
        },
    },
});
