/**
 * @package admin
 *
 * @private
 */

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default function initializeActionButtons(): void {
    Cicada.ExtensionAPI.handle('actionButtonAdd', (configuration) => {
        Cicada.State.commit('actionButtons/add', configuration);
    });
}
