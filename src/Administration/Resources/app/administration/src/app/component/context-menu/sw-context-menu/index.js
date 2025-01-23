import template from './sw-context-menu.html.twig';
import './sw-context-menu.scss';

const { Component } = Cicada;

/**
 * @sw-package framework
 *
 * @private
 */
Component.register('sw-context-menu', {
    template,

    compatConfig: Cicada.compatConfig,
});
