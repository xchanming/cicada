/**
 * @package admin
 */
import initializeNotifications from 'src/app/init/notification.init';
import { notification } from '@cicada-ag/meteor-admin-sdk';

describe('src/app/init/notification.init.ts', () => {
    beforeAll(() => {
        initializeNotifications();
    });

    beforeEach(() => {
        Cicada.State.get('notification').growlNotifications = {};
    });

    it('should handle notificationDispatch requests', async () => {
        await notification.dispatch({
            title: 'Your title',
            message: 'Your message',
            variant: 'success',
            appearance: 'notification',
            growl: true,
            actions: [
                {
                    label: 'No',
                    method: () => {},
                },
                {
                    label: 'Cancel',
                    route: 'https://www.cicada.com',
                    disabled: false,
                },
            ],
        });

        const growlNotificationKey = Object.keys(Cicada.State.get('notification').growlNotifications)[0];
        expect(Cicada.State.get('notification').growlNotifications).toEqual({
            [growlNotificationKey]: expect.objectContaining({
                title: 'Your title',
                message: 'Your message',
                variant: 'success',
            }),
        });
    });

    it('should handle notificationDispatch requests with fallback', async () => {
        await notification.dispatch({});

        const growlNotificationKey = Object.keys(Cicada.State.get('notification').growlNotifications)[0];
        expect(Cicada.State.get('notification').growlNotifications).toEqual({
            [growlNotificationKey]: expect.objectContaining({
                title: 'global.notification.noTitle',
                message: 'global.notification.noMessage',
                variant: 'info',
            }),
        });
    });
});
