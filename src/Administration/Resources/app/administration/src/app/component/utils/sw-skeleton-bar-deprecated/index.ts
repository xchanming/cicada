import template from './sw-skeleton-bar-deprecated.html.twig';
import './sw-skeleton-bar.scss';

const { Component } = Cicada;

/**
 * @private
 */
Component.register('sw-skeleton-bar-deprecated', {
    template,

    compatConfig: Cicada.compatConfig,
});
