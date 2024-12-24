import template from './sw-extension-adding-failed.html.twig';
import './sw-extension-adding-failed.scss';

const { Component } = Cicada;
const { mapState } = Component.getComponentHelper();

/**
 * @package checkout
 * @private
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'cicadaExtensionService',
    ],

    emits: ['close'],

    props: {
        extensionName: {
            type: String,
            required: true,
        },

        title: {
            type: String,
            required: false,
            default: null,
        },

        detail: {
            type: String,
            required: false,
            default: null,
        },

        documentationLink: {
            type: String,
            required: false,
            default: null,
        },
    },

    computed: {
        ...mapState('cicadaExtensions', ['myExtensions']),

        extension() {
            return this.myExtensions.data.find((extension) => {
                return extension.name === this.extensionName;
            });
        },

        isRent() {
            return this.extension?.storeLicense?.variant === this.cicadaExtensionService.EXTENSION_VARIANT_TYPES.RENT;
        },

        headline() {
            if (this.extension === undefined) {
                return this.$tc('sw-extension-store.component.sw-extension-adding-failed.titleFailure');
            }

            return this.$tc('sw-extension-store.component.sw-extension-adding-failed.installationFailed.titleFailure');
        },

        text() {
            if (this.extension === undefined) {
                return this.$tc('sw-extension-store.component.sw-extension-adding-failed.textProblem');
            }

            return this.$tc('sw-extension-store.component.sw-extension-adding-failed.installationFailed.textProblem');
        },
    },
};
