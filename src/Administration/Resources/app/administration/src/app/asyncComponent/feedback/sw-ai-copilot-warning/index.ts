import template from './sw-ai-copilot-warning.html.twig';
import './sw-ai-copilot-warning.scss';

/**
 * @sw-package framework
 * @private
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    props: {
        text: {
            type: String,
            required: false,
            default: '',
        },
    },

    computed: {
        warningText(): string {
            return this.text || this.$tc('sw-ai-copilot-warning.text');
        },
    },
});
