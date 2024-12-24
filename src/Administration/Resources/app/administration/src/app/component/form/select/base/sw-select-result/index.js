import './sw-select-result.scss';
import template from './sw-select-result.html.twig';

const { Component } = Cicada;

/**
 * @package admin
 *
 * @private
 * @status ready
 * @description Base component for select results.
 * @example-type code-only
 */
Component.register('sw-select-result', {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'setActiveItemIndex',
        'feature',
    ],

    props: {
        index: {
            type: Number,
            required: true,
        },
        item: {
            type: Object,
            required: true,
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false,
        },
        selected: {
            type: Boolean,
            required: false,
            default: false,
        },
        descriptionPosition: {
            type: String,
            required: false,
            default: 'right',
            validValues: [
                'bottom',
                'right',
                'left',
            ],
            validator(value) {
                return [
                    'bottom',
                    'right',
                    'left',
                ].includes(value);
            },
        },
        ariaLabel: {
            type: String,
            required: false,
            default: undefined,
        },
    },

    data() {
        return {
            active: false,
        };
    },

    computed: {
        resultClasses() {
            return [
                {
                    'is--active': this.active,
                    'is--disabled': this.disabled,
                    'has--description': this.hasDescriptionSlot,
                    [`is--description-${this.descriptionPosition}`]: this.hasDescriptionSlot,
                },
                `sw-select-option--${this.index}`,
            ];
        },

        hasDescriptionSlot() {
            if (this.isCompatEnabled('INSTANCE_SCOPED_SLOTS')) {
                return !!this.$slots.description || !!this.$scopedSlots.description;
            }

            return !!this.$slots.description;
        },
    },

    created() {
        this.createdComponent();
    },

    unmounted() {
        this.destroyedComponent();
    },

    methods: {
        createdComponent() {
            if (this.isCompatEnabled('INSTANCE_EVENT_EMITTER')) {
                this.$parent.$parent.$parent.$parent.$parent.$on('active-item-change', this.checkIfActive);
                this.$parent.$parent.$parent.$parent.$parent.$on('active-item-change', this.checkIfActive);
                this.$parent.$parent.$parent.$parent.$parent.$on('item-select-by-keyboard', this.checkIfSelected);
            } else {
                Cicada.Utils.EventBus.on('active-item-change', this.checkIfActive);
                Cicada.Utils.EventBus.on('item-select-by-keyboard', this.checkIfSelected);
            }
        },

        destroyedComponent() {
            if (this.isCompatEnabled('INSTANCE_EVENT_EMITTER')) {
                this.$parent.$parent.$parent.$parent.$parent.$off('active-item-change', this.checkIfActive);
                this.$parent.$parent.$parent.$parent.$parent.$off('item-select-by-keyboard', this.checkIfSelected);
            } else {
                Cicada.Utils.EventBus.off('active-item-change', this.checkIfActive);
                Cicada.Utils.EventBus.off('item-select-by-keyboard', this.checkIfSelected);
            }
        },

        checkIfSelected(selectedItemIndex) {
            if (selectedItemIndex === this.index) this.onClickResult({});
        },

        checkIfActive(activeItemIndex, { shouldFocus } = { shouldFocus: false }) {
            this.active = this.index === activeItemIndex;

            if (this.active && shouldFocus) {
                this.$el.focus();
            }
        },

        onClickResult() {
            if (this.disabled) {
                return;
            }

            if (this.isCompatEnabled('INSTANCE_EVENT_EMITTER')) {
                this.$parent.$parent.$parent.$parent.$parent.$emit('item-select', this.item);
            } else {
                Cicada.Utils.EventBus.emit('item-select', this.item);
            }
        },

        onMouseEnter() {
            this.setActiveItemIndex(this.index);
        },
    },
});
