import type { Toast } from '@cicada-ag/meteor-component-library/dist/esm/components/feedback-indicator/mt-toast/mt-toast';
import template from './sw-admin.html.twig';

const { Component } = Cicada;

/**
 * @sw-package framework
 *
 * @private
 */
Component.register('sw-admin', {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'userActivityService',
        'loginService',
        'feature',
    ],

    metaInfo() {
        return {
            title: this.$tc('global.sw-admin-menu.textCicadaAdmin'),
        };
    },

    data(): {
        channel: BroadcastChannel | null;
        toasts: Toast[];
    } {
        return {
            channel: null,
            toasts: [],
        };
    },

    computed: {
        isLoggedIn() {
            return this.loginService.isLoggedIn();
        },

        /**
         * @experimental stableVersion:v6.8.0 feature:ADMIN_COMPOSITION_API_EXTENSION_SYSTEM
         */
        overrideComponents() {
            return Component.getOverrideComponents();
        },
    },

    created() {
        Cicada.ExtensionAPI.handle('toastDispatch', (toast) => {
            this.toasts = [
                {
                    id: Cicada.Utils.createId(),
                    ...toast,
                },
                ...this.toasts,
            ];
        });

        this.channel = new BroadcastChannel('session_channel');
        this.channel.onmessage = (event) => {
            const data = event.data as { inactive?: boolean };

            if (!data || !Cicada.Utils.object.hasOwnProperty(data, 'inactive')) {
                return;
            }

            // eslint-disable-next-line max-len,@typescript-eslint/no-unsafe-member-access
            const currentRouteName = this.$router.currentRoute.value.name as string;
            const routeBlocklist = [
                'sw.inactivity.login.index',
                'sw.login.index.login',
            ];
            if (!data.inactive || routeBlocklist.includes(currentRouteName || '')) {
                return;
            }

            this.loginService.forwardLogout(true, true);
        };
    },

    beforeUnmount() {
        this.channel?.close();
    },

    methods: {
        onUserActivity() {
            this.userActivityService.updateLastUserActivity();
        },

        onRemoveToast(id: number) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    },
});
