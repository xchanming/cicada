/**
 * @sw-package unknown
 */

import { compatUtils } from '@vue/compat';

const { Component } = Cicada;

/**
 * @private
 */
Component.register('sw-vnode-renderer', {
    ...(() => {
        if (compatUtils.isCompatEnabled('COMPONENT_FUNCTIONAL')) {
            return {
                functional: true,
            };
        }

        return {};
    })(),

    compatConfig: Cicada.compatConfig,

    render(firstArgument, secondArgument) {
        const h = firstArgument;

        // Vue2 syntax
        if (typeof h === 'function') {
            const context = secondArgument;

            return context.props.node;
        }

        // Vue3 syntax
        return this.node;
    },

    props: {
        node: {
            type: Object,
            required: true,
        },
    },
});
