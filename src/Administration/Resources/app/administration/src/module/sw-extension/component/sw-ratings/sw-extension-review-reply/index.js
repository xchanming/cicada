import template from './sw-extension-review-reply.html.twig';
import './sw-extension-review-reply.scss';

const { date } = Cicada.Utils.format;

/**
 * @private
 * @package checkout
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        reply: {
            type: Object,
            required: true,
        },

        producerName: {
            type: String,
            required: true,
        },
    },

    computed: {
        creationDate() {
            return this.reply.creationDate !== null ? date(this.reply.creationDate) : null;
        },
    },
};
