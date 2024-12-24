import template from './sw-checkbox-field.html.twig';

const { Component } = Cicada;

/**
 * @package admin
 *
 * @private
 * @status ready
 * @description Wrapper component for sw-checkbox-field and mt-checkbox-field. Autoswitches between the two components.
 */
Component.register('sw-checkbox-field', {
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        modelValue: {
            type: String,
            required: false,
            default: null,
        },

        value: {
            type: Boolean,
            required: false,
            default: null,
        },
    },

    computed: {
        useMeteorComponent() {
            // Use new meteor component in major
            if (Cicada.Feature.isActive('v6.7.0.0')) {
                return true;
            }

            // Throw warning when deprecated component is used
            Cicada.Utils.debug.warn(
                'sw-checkbox-field',
                // eslint-disable-next-line max-len
                'The old usage of "sw-checkbox-field" is deprecated and will be removed in v6.7.0.0. Please use "mt-checkbox" instead.',
            );

            return false;
        },

        listeners() {
            // eslint-disable-next-line @typescript-eslint/no-unsafe-call,@typescript-eslint/no-unsafe-member-access
            if (this.isCompatEnabled('INSTANCE_LISTENERS')) {
                return this.$listeners;
            }

            return {};
        },

        compatValue: {
            get() {
                if (this.value === null || this.value === undefined) {
                    return this.modelValue;
                }

                return this.value;
            },
            set(value: string) {
                this.$emit('update:value', value);
                this.$emit('update:modelValue', value);
            },
        },
    },

    methods: {
        getSlots() {
            // eslint-disable-next-line @typescript-eslint/no-unsafe-call,@typescript-eslint/no-unsafe-member-access
            if (this.isCompatEnabled('INSTANCE_SCOPED_SLOTS')) {
                return {
                    ...this.$slots,
                    ...this.$scopedSlots,
                };
            }

            return this.$slots;
        },

        handleUpdateChecked(event: unknown) {
            this.$emit('update:checked', event);

            // Emit old event for backwards compatibility
            this.$emit('update:value', event);
        },
    },
});
