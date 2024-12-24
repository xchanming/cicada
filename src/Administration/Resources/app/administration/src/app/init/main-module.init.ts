/**
 * @package admin
 *
 * @private
 */
export default function initMainModules(): void {
    Cicada.ExtensionAPI.handle('mainModuleAdd', async (mainModuleConfig, additionalInformation) => {
        const extensionName = Object.keys(Cicada.State.get('extensions')).find((key) =>
            Cicada.State.get('extensions')[key].baseUrl.startsWith(additionalInformation._event_.origin),
        );

        if (!extensionName) {
            throw new Error(`Extension with the origin "${additionalInformation._event_.origin}" not found.`);
        }

        const extension = Cicada.State.get('extensions')?.[extensionName];

        await Cicada.State.dispatch('extensionSdkModules/addModule', {
            heading: mainModuleConfig.heading,
            locationId: mainModuleConfig.locationId,
            displaySearchBar: mainModuleConfig.displaySearchBar ?? true,
            baseUrl: extension.baseUrl,
        }).then((moduleId) => {
            if (typeof moduleId !== 'string') {
                return;
            }

            Cicada.State.commit('extensionMainModules/addMainModule', {
                extensionName,
                moduleId,
            });
        });
    });

    Cicada.ExtensionAPI.handle('smartBarButtonAdd', (configuration) => {
        Cicada.State.commit('extensionSdkModules/addSmartBarButton', configuration);
    });

    Cicada.ExtensionAPI.handle('smartBarHide', (configuration) => {
        Cicada.State.commit('extensionSdkModules/addHiddenSmartBar', configuration.locationId);
    });
}
