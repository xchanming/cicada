import type { buttonProps } from '@cicada-ag/meteor-admin-sdk/es/ui/modal';
import type { ModalItemEntry } from 'src/app/state/modals.store';
import template from './sw-modals-renderer.html.twig';

const { Component } = Cicada;

/**
 * @package admin
 *
 * @private
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Component.register('sw-modals-renderer', {
    template,

    compatConfig: Cicada.compatConfig,

    computed: {
        modals(): ModalItemEntry[] {
            return Cicada.State.get('modals').modals;
        },
    },

    methods: {
        closeModal(locationId: string) {
            Cicada.State.commit('modals/closeModal', locationId);
        },

        buttonProps(button: buttonProps) {
            return {
                method: button.method ?? ((): undefined => undefined),
                label: button.label ?? '',
                size: button.size ?? '',
                variant: button.variant ?? '',
                square: button.square ?? false,
            };
        },
    },
});
