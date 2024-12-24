import './sw-notification-center-item.scss';
import template from './sw-notification-center-item.html.twig';

const { Component } = Cicada;

/**
 * @private
 */
Component.register('sw-notification-center-item', {
    template,

    compatConfig: Cicada.compatConfig,

    emits: ['center-close'],

    props: {
        notification: {
            type: Object,
            required: true,
        },
    },

    computed: {
        itemHeaderClass() {
            return {
                'sw-notification-center-item__header--is-new': !this.notification.visited,
            };
        },

        notificationActions() {
            return this.notification.actions.filter((action) => {
                return action.route;
            });
        },
    },

    methods: {
        isNotificationFromSameDay() {
            const timestamp = this.notification.timestamp;
            const now = new Date();
            return (
                timestamp.getDate() === now.getDate() &&
                timestamp.getMonth() === now.getMonth() &&
                timestamp.getFullYear() === now.getFullYear()
            );
        },

        onDelete() {
            Cicada.State.commit('notification/removeNotification', this.notification);
        },

        handleAction(action) {
            // Allow external links for example to the cicada account or store
            if (Cicada.Utils.string.isUrl(action.route)) {
                window.open(action.route);
                return;
            }

            this.$router.push(action.route);
            this.$emit('center-close');
        },
    },
});
