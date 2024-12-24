/**
 * @package buyers-experience
 */

import template from './sw-admin-menu-extension.html.twig';

const { Component } = Cicada;

Component.override('sw-admin-menu', {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['acl'],

    computed: {
        canViewSalesChannels() {
            return this.acl.can('sales_channel.viewer');
        },
    },
});
