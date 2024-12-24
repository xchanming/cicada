import template from './sw-cicada-updates-requirements.html.twig';

const { Component } = Cicada;

/**
 * @package services-settings
 * @private
 */
Component.register('sw-settings-cicada-updates-requirements', {
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        updateInfo: {
            type: Object,
            required: true,
            default: () => {},
        },
        requirements: {
            type: Array,
            required: true,
            default: () => [],
        },
        isLoading: {
            type: Boolean,
        },
    },

    data() {
        return {
            columns: [
                {
                    property: 'message',
                    label: this.$t('sw-settings-cicada-updates.requirements.columns.message'),
                    rawData: true,
                },
                {
                    property: 'result',
                    label: this.$t('sw-settings-cicada-updates.requirements.columns.status'),
                    rawData: true,
                },
            ],
        };
    },
});
