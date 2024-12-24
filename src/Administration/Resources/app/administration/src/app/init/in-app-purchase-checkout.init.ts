/**
 * @package checkout
 *
 * @private
 */
import 'src/app/store/in-app-purchase-checkout.store';

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default function initializeInAppPurchaseCheckout(): void {
    Cicada.ExtensionAPI.handle('iapCheckout', (entry, { _event_ }) => {
        const extension = Object.values(Cicada.State.get('extensions')).find((ext) =>
            ext.baseUrl.startsWith(_event_.origin),
        );

        if (!extension) {
            throw new Error(`Extension with the origin "${_event_.origin}" not found.`);
        }

        const store = Cicada.Store.get('inAppPurchaseCheckout');
        store.request(entry, extension);
    });
}
