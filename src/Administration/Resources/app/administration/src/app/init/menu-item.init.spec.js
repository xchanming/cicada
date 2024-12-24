/**
 * @package admin
 */
import initMenuItems from 'src/app/init/menu-item.init';
import { ui } from '@cicada-ag/meteor-admin-sdk';

let stateDispatchBackup = null;
describe('src/app/init/menu-item.init.ts', () => {
    beforeAll(() => {
        initMenuItems();
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

    it('should handle incoming menuItemAdd requests', async () => {
        await ui.menu.addMenuItem({
            label: 'Test item',
            locationId: 'your-location-id',
            displaySearchBar: true,
            displaySmartBar: true,
            parent: 'sw-catalogue',
        });

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(1);
    });

    it('should not handle requests when extension is not valid', async () => {
        Cicada.State._store.state.extensions = {};

        await expect(async () => {
            await ui.menu.addMenuItem({
                label: 'Test item',
                locationId: 'your-location-id',
                displaySearchBar: true,
                displaySmartBar: true,
                parent: 'sw-catalogue',
            });
        }).rejects.toThrow(new Error('Extension with the origin "" not found.'));

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(0);
    });

    it('should not commit the extension when moduleID could not be generated', async () => {
        jest.spyOn(Cicada.State, 'dispatch').mockImplementationOnce(() => {
            return Promise.resolve(null);
        });

        await ui.menu.addMenuItem({
            label: 'Test item',
            locationId: 'your-location-id',
            displaySearchBar: true,
            displaySmartBar: true,
            parent: 'sw-catalogue',
        });

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(0);
    });

    it('should handle incoming menuCollapse/menuExpand requests', async () => {
        await ui.menu.collapseMenu();
        expect(Cicada.Store.get('adminMenu').isExpanded).toBe(false);

        await ui.menu.expandMenu();
        expect(Cicada.Store.get('adminMenu').isExpanded).toBe(true);
    });
});
