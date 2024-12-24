import template from './sw-sidebar-navigation-item.html.twig';
import './sw-sidebar-navigation-item.scss';

const { Component } = Cicada;

/**
 * @package admin
 *
 * @private
 */
Component.register('sw-sidebar-navigation-item', {
    template,

    compatConfig: Cicada.compatConfig,

    emits: ['item-click'],

    props: {
        sidebarItem: {
            type: Object,
            required: true,
        },
    },

    computed: {
        badgeTypeClasses() {
            return [
                `is--${this.sidebarItem.badgeType}`,
            ];
        },
    },

    methods: {
        emitButtonClicked() {
            this.$emit('item-click', this.sidebarItem);
        },
    },
});
