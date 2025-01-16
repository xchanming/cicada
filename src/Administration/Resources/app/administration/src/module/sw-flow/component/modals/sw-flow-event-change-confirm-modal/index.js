import template from './sw-flow-event-change-confirm-modal.html.twig';
import './sw-flow-event-change-confirm-modal.scss';

const { Component, State } = Cicada;
const { EntityCollection } = Cicada.Data;
const { mapGetters } = Component.getComponentHelper();

/**
 * @private
 * @sw-package after-sales
 */
export default {
    template,

    emits: [
        'modal-confirm',
        'modal-close',
    ],

    compatConfig: Cicada.compatConfig,

    computed: {
        ...mapGetters('swFlowState', ['sequences']),
    },

    methods: {
        onConfirm() {
            const sequencesCollection = new EntityCollection(
                this.sequences.source,
                this.sequences.entity,
                Cicada.Context.api,
                null,
                [],
            );

            State.commit('swFlowState/setSequences', sequencesCollection);

            this.$emit('modal-confirm');
            this.onClose();
        },

        onClose() {
            this.$emit('modal-close');
        },
    },
};
