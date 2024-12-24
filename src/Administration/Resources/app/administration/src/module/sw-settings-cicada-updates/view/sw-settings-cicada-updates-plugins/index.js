import template from './sw-cicada-updates-plugins.html.twig';

const { Component } = Cicada;

/**
 * @package services-settings
 * @private
 */
Component.register('sw-settings-cicada-updates-plugins', {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['feature'],

    props: {
        isLoading: {
            type: Boolean,
        },
        plugins: {
            type: Array,
            default: () => [],
        },
    },
    computed: {
        columns() {
            return [
                {
                    property: 'name',
                    label: this.$tc('sw-settings-cicada-updates.plugins.columns.name'),
                    rawData: true,
                },
                {
                    property: 'icon',
                    label: this.$tc('sw-settings-cicada-updates.plugins.columns.available'),
                    rawData: true,
                },
            ];
        },
    },

    methods: {
        openMyExtensions() {
            this.$router.push({
                name: 'sw.extension.my-extensions.listing.app',
            });
        },
    },
});
