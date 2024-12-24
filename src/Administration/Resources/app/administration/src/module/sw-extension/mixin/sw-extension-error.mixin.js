import { defineComponent } from 'vue';

/**
 * @package checkout
 * @private
 */
export default Cicada.Mixin.register(
    'sw-extension-error',
    defineComponent({
        mixins: [Cicada.Mixin.getByName('notification')],

        methods: {
            showExtensionErrors(errorResponse) {
                Cicada.Service('extensionErrorService')
                    .handleErrorResponse(errorResponse, this)
                    .forEach((notification) => {
                        this.createNotificationError(notification);
                    });
            },
        },
    }),
);
