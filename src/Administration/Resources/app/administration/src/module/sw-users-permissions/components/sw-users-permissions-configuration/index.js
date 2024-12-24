/**
 * @package services-settings
 */
import template from './sw-users-permissions-configuration.html.twig';
import './sw-users-permissions-configuration.scss';

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['acl'],

    emits: ['loading-change'],

    methods: {
        onChangeLoading(loading) {
            this.$emit('loading-change', loading);
        },
    },
};
