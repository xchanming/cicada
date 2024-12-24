/**
 * @package admin
 */
import initializeUserContext from 'src/app/init-post/user-information.init';
import { initializeUserNotifications } from 'src/app/state/notification.store';

jest.mock('src/app/state/notification.store', () => ({
    initializeUserNotifications: jest.fn(),
}));

describe('src/app/init-post/user-information.init.ts', () => {
    let isLoggedIn = true;
    const logoutMock = jest.fn(() => true);
    let userData = {
        data: {
            username: 'my-fancy-username',
            password: 'my-strong-password',
        },
    };

    beforeAll(() => {
        Cicada.Service().register('loginService', () => {
            return {
                isLoggedIn: () => isLoggedIn,
                logout: logoutMock,
            };
        });

        Cicada.Service().register('userService', () => {
            return {
                getUser: () => Promise.resolve(userData),
            };
        });
    });

    beforeEach(() => {
        Cicada.State.commit('setCurrentUser', undefined);
        initializeUserNotifications.mockClear();
        logoutMock.mockClear();
        isLoggedIn = true;
        userData = {
            data: {
                username: 'my-fancy-username',
                password: 'my-strong-password',
            },
        };
    });

    it('should init the user context service correctly when user is logged in', async () => {
        expect(initializeUserNotifications).not.toHaveBeenCalled();
        expect(Cicada.State.get('session').currentUser).toBeUndefined();

        await initializeUserContext();

        expect(initializeUserNotifications).toHaveBeenCalled();
        expect(Cicada.State.get('session').currentUser).toEqual({
            username: 'my-fancy-username',
        });
    });

    it('should init the user context service correctly when user is not logged in', async () => {
        isLoggedIn = false;

        expect(initializeUserNotifications).not.toHaveBeenCalled();
        expect(logoutMock).not.toHaveBeenCalled();
        expect(Cicada.State.get('session').currentUser).toBeUndefined();

        await initializeUserContext();

        expect(logoutMock).toHaveBeenCalled();
        expect(initializeUserNotifications).not.toHaveBeenCalled();
        expect(Cicada.State.get('session').currentUser).toBeUndefined();
    });

    it('should call logout when user value is not correct', async () => {
        userData = {
            foo: 'not-working',
        };

        expect(initializeUserNotifications).not.toHaveBeenCalled();
        expect(logoutMock).not.toHaveBeenCalled();
        expect(Cicada.State.get('session').currentUser).toBeUndefined();

        await initializeUserContext();

        expect(logoutMock).toHaveBeenCalled();
        expect(initializeUserNotifications).not.toHaveBeenCalled();
        expect(Cicada.State.get('session').currentUser).toBeUndefined();
    });
});
