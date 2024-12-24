/**
 * @package admin
 */
import initializeSettingItems from 'src/app/init/settings-item.init';
import { ui } from '@cicada-ag/meteor-admin-sdk';

let stateDispatchBackup = null;
describe('src/app/init/settings-item.init.ts', () => {
    beforeAll(() => {
        initializeSettingItems();
        stateDispatchBackup = Cicada.State.dispatch;
    });

    beforeEach(() => {
        Object.defineProperty(Cicada.State, 'dispatch', {
            value: stateDispatchBackup,
            writable: true,
            configurable: true,
        });
        Cicada.State.get('extensionSdkModules').modules = [];
        Cicada.State.get('settingsItems').settingsGroups = {
            shop: [],
            system: [],
            plugins: [],
        };

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

    it('should handle the settingsItemAdd requests', async () => {
        await ui.settings.addSettingsItem({
            label: 'App Settings',
            locationId: 'settings-location-id',
            icon: 'default-object-books',
            displaySearchBar: true,
            tab: 'system',
        });

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(1);
        expect(Cicada.State.get('extensionSdkModules').modules[0]).toEqual({
            baseUrl: '',
            displaySearchBar: true,
            heading: 'App Settings',
            id: expect.any(String),
            locationId: 'settings-location-id',
        });

        expect(Cicada.State.get('settingsItems').settingsGroups.system).toHaveLength(1);
        expect(Cicada.State.get('settingsItems').settingsGroups.system[0]).toEqual({
            group: 'system',
            icon: 'default-object-books',
            id: 'settings-location-id',
            label: 'App Settings',
            name: 'settings-location-id',
            to: {
                name: 'sw.extension.sdk.index',
                params: {
                    id: expect.any(String),
                    back: 'sw.settings.index.system',
                },
            },
        });
    });

    it('should handle the settingsItemAdd requests with fallback', async () => {
        await ui.settings.addSettingsItem({
            label: 'App Settings',
            locationId: 'settings-location-id',
            icon: 'default-object-books',
            displaySearchBar: true,
        });

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(1);
        expect(Cicada.State.get('extensionSdkModules').modules[0]).toEqual({
            baseUrl: '',
            displaySearchBar: true,
            heading: 'App Settings',
            id: expect.any(String),
            locationId: 'settings-location-id',
        });

        expect(Cicada.State.get('settingsItems').settingsGroups.plugins).toHaveLength(1);
        expect(Cicada.State.get('settingsItems').settingsGroups.plugins[0]).toEqual({
            group: 'plugins',
            icon: 'default-object-books',
            id: 'settings-location-id',
            label: 'App Settings',
            name: 'settings-location-id',
            to: {
                name: 'sw.extension.sdk.index',
                params: {
                    id: expect.any(String),
                    back: 'sw.settings.index.plugins',
                },
            },
        });
    });

    it('should handle the settingsItemAdd requests with unallowed tab', async () => {
        await ui.settings.addSettingsItem({
            label: 'App Settings',
            locationId: 'settings-location-id',
            icon: 'default-object-books',
            displaySearchBar: true,
            tab: 'not-allowed',
        });

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(1);
        expect(Cicada.State.get('extensionSdkModules').modules[0]).toEqual({
            baseUrl: '',
            displaySearchBar: true,
            heading: 'App Settings',
            id: expect.any(String),
            locationId: 'settings-location-id',
        });

        expect(Cicada.State.get('settingsItems').settingsGroups.plugins).toHaveLength(1);
        expect(Cicada.State.get('settingsItems').settingsGroups.plugins[0]).toEqual({
            group: 'plugins',
            icon: 'default-object-books',
            id: 'settings-location-id',
            label: 'App Settings',
            name: 'settings-location-id',
            to: {
                name: 'sw.extension.sdk.index',
                params: {
                    id: expect.any(String),
                    back: 'sw.settings.index.plugins',
                },
            },
        });
    });

    it('should not handle requests when extension is not valid', async () => {
        Cicada.State._store.state.extensions = {};

        await expect(async () => {
            await ui.settings.addSettingsItem({
                label: 'App Settings',
                locationId: 'settings-location-id',
                icon: 'default-object-books',
                displaySearchBar: true,
                tab: 'plugins',
            });
        }).rejects.toThrow(new Error('Extension with the origin "" not found.'));

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(0);
    });

    it('should not commit the extension when moduleID could not be generated', async () => {
        jest.spyOn(Cicada.State, 'dispatch').mockImplementationOnce(() => {
            return Promise.resolve(null);
        });

        await ui.settings.addSettingsItem({
            label: 'App Settings',
            locationId: 'settings-location-id',
            icon: 'default-object-books',
            displaySearchBar: true,
            tab: 'plugins',
        });

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(0);
    });
});
