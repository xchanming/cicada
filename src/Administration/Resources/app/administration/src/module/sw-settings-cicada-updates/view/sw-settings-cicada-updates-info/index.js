import './sw-cicada-updates-info.scss';
import template from './sw-cicada-updates-info.html.twig';

const { Component } = Cicada;

/**
 * @sw-package framework
 * @private
 */
Component.register('sw-settings-cicada-updates-info', {
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        changelog: {
            type: String,
            required: true,
        },
        isLoading: {
            type: Boolean,
        },
    },
});
