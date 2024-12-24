/**
 * @package admin
 */
import { ui } from '@cicada-ag/meteor-admin-sdk';
import initMainModules from 'src/app/init/main-module.init';

let stateDispatchBackup = null;

describe('src/app/init/main-module.init.ts', () => {
    beforeAll(() => {
        initMainModules();
        stateDispatchBackup = Cicada.State.dispatch;
    });

    beforeEach(() => {
        Object.defineProperty(Cicada.State, 'dispatch', {
            value: stateDispatchBackup,
            writable: true,
            configurable: true,
        });
        Cicada.State.get('extensionSdkModules').modules = [];

        Cicada.State._store.state.extensions = {};
        Cicada.State.commit('extensions/addExtension', {
            name: 'jestapp',
            baseUrl: '',
            permissions: [],
            version: '1.0.0',
            type: 'app',
            integrationId: '123',
            active: true,
        });
    });

    it('should init the main module handler', async () => {
        await ui.mainModule.addMainModule({
            heading: 'My awesome module',
            locationId: 'my-awesome-module',
            displaySearchBar: true,
        });

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(1);
        expect(Cicada.State.get('extensionSdkModules').modules[0]).toEqual({
            id: expect.any(String),
            baseUrl: '',
            heading: 'My awesome module',
            displaySearchBar: true,
            locationId: 'my-awesome-module',
        });
    });

    it('should not handle requests when extension is not valid', async () => {
        Cicada.State._store.state.extensions = {};

        await expect(async () => {
            await ui.mainModule.addMainModule({
                heading: 'My awesome module',
                locationId: 'my-awesome-module',
                displaySearchBar: true,
            });
        }).rejects.toThrow(new Error('Extension with the origin "" not found.'));

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(0);
    });

    it('should not commit the extension when moduleID could not be generated', async () => {
        jest.spyOn(Cicada.State, 'dispatch').mockImplementationOnce(() => {
            return Promise.resolve(null);
        });

        await ui.mainModule.addMainModule({
            heading: 'My awesome module',
            locationId: 'my-awesome-module',
            displaySearchBar: true,
        });

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(0);
    });

    it('should be able to update the hidden smart bars', async () => {
        await ui.mainModule.hideSmartBar({ locationId: 'my-awesome-module' });

        expect(Cicada.State.get('extensionSdkModules').hiddenSmartBars).toEqual(['my-awesome-module']);
    });
});
