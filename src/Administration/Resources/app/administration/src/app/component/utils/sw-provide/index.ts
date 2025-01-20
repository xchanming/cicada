/**
 * @package admin
 */
import { computed, provide } from 'vue';

/**
 * @private
 */
Cicada.Component.register('sw-provide', {
    template: '<slot />',
    inheritAttrs: false,
    setup(_props, { attrs }) {
        Object.keys(attrs).forEach((key) =>
            provide(
                Cicada.Utils.string.camelCase(key),
                computed(() => attrs[key]),
            ),
        );
        return {};
    },
});
