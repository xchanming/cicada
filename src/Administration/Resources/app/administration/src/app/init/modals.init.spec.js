/**
 * @package admin
 */
import initializeModal from 'src/app/init/modals.init';
import { ui } from '@cicada-ag/meteor-admin-sdk';

let stateDispatchBackup;
describe('src/app/init/modals.init.ts', () => {
    beforeAll(() => {
        initializeModal();
        stateDispatchBackup = Cicada.State.dispatch;
    });

    beforeEach(() => {
        Object.defineProperty(Cicada.State, 'dispatch', {
            value: stateDispatchBackup,
            writable: true,
            configurable: true,
        });
        Cicada.State.get('modals').modals = [];

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

    it('should handle incoming uiModalOpen requests', async () => {
        await ui.modal.open({
            title: 'Your modal title',
            locationId: 'your-location-id',
            variant: 'large',
            showHeader: true,
            showFooter: false,
            closable: true,
            buttons: [
                {
                    label: 'Dispatch notification',
                    method: () => {
                        // method content
                    },
                },
                {
                    label: 'Close modal',
                    variant: 'primary',
                    method: () => {
                        ui.modal.close({
                            locationId: 'your-location-id',
                        });
                    },
                },
            ],
        });

        expect(Cicada.State.get('modals').modals).toHaveLength(1);
    });

    it('should handle incoming uiModalClose requests', async () => {
        await ui.modal.open({
            title: 'Your modal title',
            locationId: 'your-location-id',
            variant: 'large',
            showHeader: true,
            showFooter: false,
            closable: true,
            buttons: [
                {
                    label: 'Dispatch notification',
                    method: () => {
                        // method content
                    },
                },
                {
                    label: 'Close modal',
                    variant: 'primary',
                    method: () => {
                        ui.modal.close({
                            locationId: 'your-location-id',
                        });
                    },
                },
            ],
        });

        expect(Cicada.State.get('modals').modals).toHaveLength(1);

        await ui.modal.close({
            locationId: 'your-location-id',
        });

        expect(Cicada.State.get('modals').modals).toHaveLength(0);
    });

    it('should not handle requests when extension is not valid', async () => {
        Cicada.State._store.state.extensions = {};

        await expect(async () => {
            await ui.modal.open({
                title: 'Your modal title',
                locationId: 'your-location-id',
                variant: 'large',
                showHeader: true,
                showFooter: false,
                closable: true,
                buttons: [
                    {
                        label: 'Dispatch notification',
                        method: () => {
                            // method content
                        },
                    },
                    {
                        label: 'Close modal',
                        variant: 'primary',
                        method: () => {
                            ui.modal.close({
                                locationId: 'your-location-id',
                            });
                        },
                    },
                ],
            });
        }).rejects.toThrow(new Error('Extension with the origin "" not found.'));

        expect(Cicada.State.get('extensionSdkModules').modules).toHaveLength(0);
    });
});
