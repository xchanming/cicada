/**
 * @package admin
 */

import template from './sw-login-recovery-info.html.twig';

const { Component } = Cicada;

/**
 * @private
 */
Component.register('sw-login-recovery-info', {
    template,

    compatConfig: Cicada.compatConfig,

    emits: ['is-not-loading'],

    computed: {
        rateLimitTime() {
            const waitTime = this.$route.params?.waitTime;
            if (typeof waitTime !== 'number') {
                return null;
            }

            return waitTime >= 1 ? waitTime : null;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.$emit('is-not-loading');
        },
    },
});
