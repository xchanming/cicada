/**
 * @sw-package after-sales
 */
describe('module/sw-dashboard/page/sw-dashboard-index', () => {
    beforeAll(async () => {
        if (Cicada.State.get('session')) {
            Cicada.State.unregisterModule('session');
        }

        Cicada.State.registerModule('session', {
            state: {
                currentUser: null,
            },
            mutations: {
                setCurrentUser(state, user) {
                    state.currentUser = user;
                },
            },
        });
        jest.useFakeTimers('modern');
    });

    afterAll(() => {
        jest.useRealTimers();
    });

    it('sample test', () => {
        expect(1 + 1).toBe(2);
    });
});
