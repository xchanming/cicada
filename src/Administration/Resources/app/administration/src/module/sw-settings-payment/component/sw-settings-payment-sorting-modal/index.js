import template from './sw-settings-payment-sorting-modal.html.twig';
import './sw-settings-payment-sorting-modal.scss';

const { Mixin } = Cicada;

/**
 * @package checkout
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'acl',
        'repositoryFactory',
        'feature',
    ],

    emits: [
        'modal-close',
        'modal-save',
    ],

    mixins: [Mixin.getByName('notification')],

    props: {
        paymentMethods: {
            type: Array,
            required: true,
        },
    },

    data() {
        return {
            isSaving: false,
            originalPaymentMethods: [...this.paymentMethods],
            sortedPaymentMethods: [...this.paymentMethods],
            scrollOnDragConf: {
                speed: 50,
                margin: 130,
                accelerationMargin: -10,
            },
        };
    },

    computed: {
        paymentMethodRepository() {
            return this.repositoryFactory.create('payment_method');
        },

        assetFilter() {
            return Cicada.Filter.getByName('asset');
        },
    },

    methods: {
        closeModal() {
            this.$emit('modal-close');
        },

        applyChanges() {
            this.isSaving = true;

            this.sortedPaymentMethods.map((paymentMethod, index) => {
                paymentMethod.position = index + 1;
                return paymentMethod;
            });

            return this.paymentMethodRepository
                .saveAll(this.sortedPaymentMethods, Cicada.Context.api)
                .then(() => {
                    this.isSaving = false;
                    this.$emit('modal-close');
                    this.$emit('modal-save');

                    this.createNotificationSuccess({
                        message: this.$tc('sw-settings-payment.sorting-modal.saveSuccessful'),
                    });
                })
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('sw-settings-payment.sorting-modal.errorMessage'),
                    });
                });
        },

        onSort(sortedItems) {
            this.sortedPaymentMethods = sortedItems;
        },

        isCicadaDefaultPaymentMethod(paymentMethod) {
            const defaultPaymentMethods = [
                'Cicada\\Core\\Checkout\\Payment\\Cart\\PaymentHandler\\CashPayment',
                'Cicada\\Core\\Checkout\\Payment\\Cart\\PaymentHandler\\PrePayment',
            ];

            return defaultPaymentMethods.includes(paymentMethod.handlerIdentifier);
        },
    },
};
