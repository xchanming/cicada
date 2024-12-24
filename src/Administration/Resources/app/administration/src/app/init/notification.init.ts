/**
 * @package admin
 *
 * @private
 */
export default function initializeNotifications(): void {
    // Handle incoming notifications from the ExtensionAPI
    Cicada.ExtensionAPI.handle('notificationDispatch', async (notificationOptions) => {
        const message = notificationOptions.message ?? Cicada.Snippet.tc('global.notification.noMessage');
        const title = notificationOptions.title ?? Cicada.Snippet.tc('global.notification.noTitle');
        const actions = notificationOptions.actions ?? [];
        const appearance = notificationOptions.appearance ?? 'notification';
        const growl = notificationOptions.growl ?? true;
        const variant = notificationOptions.variant ?? 'info';

        await Cicada.State.dispatch('notification/createNotification', {
            variant: variant,
            title: title,
            message: message,
            growl: growl,
            actions: actions,
            system: appearance === 'system',
        });
    });
}
