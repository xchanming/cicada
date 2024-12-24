/**
 * @package admin
 *
 * @private
 */
export default function initMenuItems(): void {
    Cicada.ExtensionAPI.handle('menuItemAdd', async (menuItemConfig, additionalInformation) => {
        const extension = Object.values(Cicada.State.get('extensions')).find((ext) =>
            ext.baseUrl.startsWith(additionalInformation._event_.origin),
        );

        if (!extension) {
            throw new Error(`Extension with the origin "${additionalInformation._event_.origin}" not found.`);
        }

        await Cicada.State.dispatch('extensionSdkModules/addModule', {
            heading: menuItemConfig.label,
            locationId: menuItemConfig.locationId,
            displaySearchBar: menuItemConfig.displaySearchBar,
            displaySmartBar: menuItemConfig.displaySmartBar,
            baseUrl: extension.baseUrl,
        }).then((moduleId) => {
            if (typeof moduleId !== 'string') {
                return;
            }

            Cicada.State.commit('menuItem/addMenuItem', {
                ...menuItemConfig,
                moduleId,
            });
        });
    });

    Cicada.ExtensionAPI.handle('menuCollapse', () => {
        Cicada.Store.get('adminMenu').collapseSidebar();
    });

    Cicada.ExtensionAPI.handle('menuExpand', () => {
        Cicada.Store.get('adminMenu').expandSidebar();
    });
}
