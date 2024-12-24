import './sw-wizard-dot-navigation.scss';
import template from './sw-wizard-dot-navigation.html.twig';

const { Component } = Cicada;

/**
 * See `sw-wizard` for an example.
 *
 * @private
 */
Component.register('sw-wizard-dot-navigation', {
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        pages: {
            type: Array,
            required: true,
        },
        activePage: {
            type: Number,
            required: true,
        },
    },
});
