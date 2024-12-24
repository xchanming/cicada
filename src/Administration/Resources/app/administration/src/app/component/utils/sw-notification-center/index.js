import { POLL_BACKGROUND_INTERVAL, POLL_FOREGROUND_INTERVAL } from 'src/core/worker/worker-notification-listener';
import template from './sw-notification-center.html.twig';
import './sw-notification-center.scss';

const { Component, Mixin } = Cicada;

/**
 * @private
 */
Component.register('sw-notification-center', {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['feature'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            additionalContextMenuClasses: {
                'sw-notification-center__context-container': true,
            },
            showDeleteModal: false,
            unsubscribeFromStore: null,
        };
    },

    computed: {
        notifications() {
            return Object.values(Cicada.State.getters['notification/getNotificationsObject']).reverse();
        },

        additionalContextButtonClass() {
            return {
                'sw-notification-center__context-button--new-available': this.notifications.some((n) => !n.visited),
            };
        },
    },

    created() {
        this.unsubscribeFromStore = Cicada.State.subscribeAction(this.createNotificationFromSystemError);
        if (this.isCompatEnabled('INSTANCE_EVENT_EMITTER')) {
            this.$root.$on('on-change-notification-center-visibility', this.changeVisibility);
        } else {
            Cicada.Utils.EventBus.on('on-change-notification-center-visibility', this.changeVisibility);
        }
    },

    beforeDestroyed() {
        if (typeof this.unsubscribeFromStore === 'function') {
            this.unsubscribeFromStore();
        }

        if (!this.isCompatEnabled('INSTANCE_EVENT_EMITTER')) {
            this.$root.$off('on-change-notification-center-visibility', this.changeVisibility);
        } else {
            Cicada.Utils.EventBus.$off('on-change-notification-center-visibility', this.changeVisibility);
        }
    },

    methods: {
        onContextMenuOpen() {
            Cicada.State.commit('notification/setWorkerProcessPollInterval', POLL_FOREGROUND_INTERVAL);
        },
        onContextMenuClose() {
            Cicada.State.dispatch('notification/setAllNotificationsVisited');
            Cicada.State.commit('notification/setWorkerProcessPollInterval', POLL_BACKGROUND_INTERVAL);
        },
        openDeleteModal() {
            this.showDeleteModal = true;
        },
        onConfirmDelete() {
            Cicada.State.commit('notification/clearNotificationsForCurrentUser');
            this.showDeleteModal = false;
        },
        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },
        changeVisibility(visible) {
            if (this.$refs.notificationCenterContextButton === undefined) {
                return;
            }

            if (visible) {
                this.$refs.notificationCenterContextButton.openMenu();
                return;
            }

            this.$refs.notificationCenterContextButton.showMenu = false;
            this.$refs.notificationCenterContextButton.removeMenuFromBody();
            this.$refs.notificationCenterContextButton.$emit('context-menu-after-close');
        },
        createNotificationFromSystemError({ type, payload }) {
            if (type !== 'addSystemError') {
                return;
            }

            this.createSystemNotificationError({
                id: payload.id,
                message: payload.error.detail,
            });
        },
    },
});
