import template from './sw-context-menu-divider.html.twig';
import './sw-context-menu-divider.scss';

const { Component } = Cicada;

/**
 * @sw-package framework
 *
 * @private
 */
Component.register('sw-context-menu-divider', {
    template,

    compatConfig: Cicada.compatConfig,
});
