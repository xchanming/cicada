import template from './sw-flow-leave-page-modal.html.twig';

/**
 * @private
 * @package services-settings
 */
export default {
    template,

    emits: [
        'page-leave-confirm',
        'page-leave-cancel',
    ],

    compatConfig: Cicada.compatConfig,

    methods: {
        onConfirm() {
            this.$emit('page-leave-confirm');
        },
        onCancel() {
            this.$emit('page-leave-cancel');
        },
    },
};
