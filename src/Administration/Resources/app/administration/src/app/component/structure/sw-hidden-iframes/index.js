import { MAIN_HIDDEN } from '@cicada-ag/meteor-admin-sdk/es/location';
import template from './sw-hidden-iframes.html.twig';

const { Component } = Cicada;

/**
 * @package admin
 *
 * @private
 */
Component.register('sw-hidden-iframes', {
    template,

    compatConfig: Cicada.compatConfig,

    computed: {
        extensions() {
            return Cicada.State.getters['extensions/privilegedExtensions'];
        },

        MAIN_HIDDEN() {
            return MAIN_HIDDEN;
        },
    },
});
