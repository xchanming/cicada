/**
 * @package services-settings
 */
import template from './sw-admin-menu.html.twig';

const { Component } = Cicada;

Component.override('sw-admin-menu', {
    template,
    inject: ['acl'],
});
