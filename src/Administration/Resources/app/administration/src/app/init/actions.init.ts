/**
 * @package admin
 *
 * @private
 */
export default function initializeActions(): void {
    Cicada.ExtensionAPI.handle('actionExecute', async (actionConfiguration, additionalInformation) => {
        const extensionName = Object.keys(Cicada.State.get('extensions')).find((key) =>
            Cicada.State.get('extensions')[key].baseUrl.startsWith(additionalInformation._event_.origin),
        );

        if (!extensionName) {
            // eslint-disable-next-line max-len
            throw new Error(
                `Could not find an extension with the given event origin "${additionalInformation._event_.origin}"`,
            );
        }

        await Cicada.Service('extensionSdkService').runAction(
            {
                url: actionConfiguration.url,
                entity: actionConfiguration.entity,
                action: Cicada.Utils.createId(),
                appName: extensionName,
            },
            actionConfiguration.entityIds,
        );
    });
}
