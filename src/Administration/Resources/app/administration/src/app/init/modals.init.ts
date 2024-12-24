/**
 * @package admin
 *
 * @private
 */
export default function initializeModal(): void {
    // eslint-disable-next-line @typescript-eslint/require-await
    Cicada.ExtensionAPI.handle('uiModalOpen', async (modalConfig, { _event_ }) => {
        const extension = Object.values(Cicada.State.get('extensions')).find((ext) =>
            ext.baseUrl.startsWith(_event_.origin),
        );

        if (!extension) {
            throw new Error(`Extension with the origin "${_event_.origin}" not found.`);
        }

        Cicada.State.commit('modals/openModal', {
            closable: true,
            showHeader: true,
            showFooter: true,
            variant: 'default',
            baseUrl: extension.baseUrl,
            ...modalConfig,
        });
    });

    Cicada.ExtensionAPI.handle('uiModalClose', ({ locationId }) => {
        Cicada.State.commit('modals/closeModal', locationId);
    });
}
