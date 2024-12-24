/**
 * @package admin
 */

import template from './sw-gtc-checkbox.html.twig';

/**
 * @private
 */
Cicada.Component.register('sw-gtc-checkbox', {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['feature'],

    emits: ['update:value'],

    props: {
        value: {
            type: Boolean,
            required: true,
        },
    },

    methods: {
        onChange(value) {
            this.$emit('update:value', value);
        },
    },
});
