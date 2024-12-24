/**
 * @package customer-order
 */
import initTopbarButtons from 'src/app/init/topbar-button.init';
import { send } from '@cicada-ag/meteor-admin-sdk/es/channel';

describe('src/app/init/topbar-button.init.ts', () => {
    it('should handle __upsellingMenuButton', async () => {
        initTopbarButtons();

        await send('__upsellingMenuButton', {
            label: 'Test action',
            icon: 'solid-rocket',
            callback: () => {},
        });

        const buttons = Cicada.Store.get('topBarButton').buttons;
        expect(buttons).toHaveLength(1);

        const button = buttons[0];

        expect(button.hasOwnProperty('label')).toBe(true);
        expect(button.label).toBe('Test action');
    });
});
