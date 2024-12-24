/**
 * @package admin
 *
 * @private
 */
export default function initializeExtensionComponentSections(): void {
    // Handle incoming ExtensionComponentRenderer requests from the ExtensionAPI
    Cicada.ExtensionAPI.handle('uiComponentSectionRenderer', (componentConfig, additionalInformation) => {
        const extension = Object.values(Cicada.State.get('extensions')).find((ext) =>
            ext.baseUrl.startsWith(additionalInformation._event_.origin),
        );

        if (!extension) {
            throw new Error(`Extension with the origin "${additionalInformation._event_.origin}" not found.`);
        }

        Cicada.State.commit('extensionComponentSections/addSection', {
            ...componentConfig,
            extensionName: extension.name,
        });
    });
}
