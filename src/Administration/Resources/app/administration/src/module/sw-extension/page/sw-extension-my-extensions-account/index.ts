import type { AxiosError } from 'axios';
import template from './sw-extension-my-extensions-account.html.twig';
import './sw-extension-my-extensions-account.scss';
import extensionErrorHandler from '../../service/extension-error-handler.service';
import type { MappedError } from '../../service/extension-error-handler.service';
import type { UserInfo } from '../../../../core/service/api/store.api.service';

const { State, Mixin, Filter } = Cicada;

/**
 * @package checkout
 * @private
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'systemConfigApiService',
        'cicadaExtensionService',
        'storeService',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data(): {
        isLoading: boolean;
        unsubscribeStore: (() => void) | null;
        form: {
            password: string;
            cicadaId: string;
        };
    } {
        return {
            isLoading: true,
            unsubscribeStore: null,
            form: {
                password: '',
                cicadaId: '',
            },
        };
    },

    computed: {
        userInfo(): UserInfo | null {
            return State.get('cicadaExtensions').userInfo;
        },

        isLoggedIn(): boolean {
            return State.get('cicadaExtensions').userInfo !== null;
        },

        assetFilter() {
            return Filter.getByName('asset');
        },
    },

    created() {
        this.createdComponent()
            .then(() => {
                // component functions are always bound to this
                // eslint-disable-next-line @typescript-eslint/unbound-method
                this.unsubscribeStore = State.subscribe(this.showErrorNotification);
            })
            // eslint-disable-next-line @typescript-eslint/no-empty-function
            .catch(() => {});
    },

    beforeUnmount() {
        if (this.unsubscribeStore !== null) {
            this.unsubscribeStore();
        }
    },

    methods: {
        async createdComponent() {
            try {
                this.isLoading = true;
                await this.cicadaExtensionService.checkLogin();
            } finally {
                this.isLoading = false;
            }
        },

        async logout() {
            try {
                await this.storeService.logout();
                this.$emit('logout-success');
            } catch (errorResponse) {
                this.commitErrors(
                    errorResponse as AxiosError<{
                        errors: StoreApiException[];
                    }>,
                );
            } finally {
                await this.cicadaExtensionService.checkLogin();
            }
        },

        async login() {
            this.isLoading = true;

            try {
                await this.storeService.login(this.form.cicadaId, this.form.password);

                this.$emit('login-success');

                // eslint-disable-next-line @typescript-eslint/no-unsafe-call
                this.createNotificationSuccess({
                    message: this.$tc('sw-extension.my-extensions.account.loginNotificationMessage'),
                });
            } catch (errorResponse) {
                this.commitErrors(
                    errorResponse as AxiosError<{
                        errors: StoreApiException[];
                    }>,
                );
            } finally {
                await this.cicadaExtensionService.checkLogin();
                this.isLoading = false;
            }
        },

        showErrorNotification({ type, payload }: { type: string; payload: MappedError[] }) {
            if (type !== 'cicadaExtensions/pluginErrorsMapped') {
                return;
            }

            payload.forEach((error) => {
                if (error.parameters) {
                    this.showApiNotification(error);
                    return;
                }

                // Methods from mixins are not recognized
                // eslint-disable-next-line @typescript-eslint/no-unsafe-call
                this.createNotificationError({
                    message: this.$tc(error.message),
                });
            });
        },

        showApiNotification(error: MappedError) {
            // @ts-expect-error
            const docLink = this.$tc('sw-extension.errors.messageToTheCicadaDocumentation', 0, error.parameters);

            // Methods from mixins are not recognized
            // eslint-disable-next-line @typescript-eslint/no-unsafe-call
            this.createNotificationError({
                title: error.title,
                message: `${error.message} ${docLink}`,
                autoClose: false,
            });
        },

        commitErrors(errorResponse: AxiosError<{ errors: StoreApiException[] }>): never {
            if (errorResponse.response) {
                const mappedErrors = extensionErrorHandler.mapErrors(errorResponse.response.data.errors);
                Cicada.State.commit('cicadaExtensions/pluginErrorsMapped', mappedErrors);
            }

            throw errorResponse;
        },
    },
});
