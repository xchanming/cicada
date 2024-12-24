/**
 * @package services-settings
 */
import template from './sw-settings-mailer-smtp.html.twig';
import './sw-settings-mailer-smtp.scss';

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    emits: [
        'host-changed',
        'port-changed',
    ],

    props: {
        mailerSettings: {
            type: Object,
            required: true,
        },
        hostError: {
            type: Object,
            required: false,
            default: null,
        },
        portError: {
            type: Object,
            required: false,
            default: null,
        },
    },

    computed: {
        isOauth() {
            return this.mailerSettings['core.mailerSettings.emailAgent'] === 'smtp+oauth';
        },

        encryptionOptions() {
            return [
                {
                    value: 'null',
                    label: this.$tc('sw-settings-mailer.encryption.no-encryption'),
                },
                {
                    value: 'ssl',
                    label: this.$tc('sw-settings-mailer.encryption.ssl'),
                },
                {
                    value: 'tls',
                    label: this.$tc('sw-settings-mailer.encryption.tls'),
                },
            ];
        },
    },
};
