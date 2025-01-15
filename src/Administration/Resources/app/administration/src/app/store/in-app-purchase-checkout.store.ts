/**
 * @package checkout
 */
import type { iapCheckout } from '@cicada-ag/meteor-admin-sdk/es/iap';
import type { Extension } from 'src/app/state/extensions.store';

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export type InAppPurchaseRequest = Omit<iapCheckout, 'responseType'>;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export type InAppPurchaseCheckoutState =
    | {
          entry: null;
          extension: null;
      }
    | {
          entry: InAppPurchaseRequest;
          extension: Extension;
      };

const inAppPurchaseCheckoutStore = Cicada.Store.register({
    id: 'inAppPurchaseCheckout',

    state: (): InAppPurchaseCheckoutState => ({
        entry: null,
        extension: null,
    }),

    actions: {
        request(entry: InAppPurchaseRequest, extension: Extension | string): void {
            if (typeof extension === 'string') {
                const extensionObject = Object.values(Cicada.State.get('extensions')).find((ext) => ext.name === extension);
                if (extensionObject === undefined) {
                    throw new Error(`Extension with the name "${extension}" not found.`);
                }
                extension = extensionObject;
            }
            this.entry = entry;
            this.extension = extension;
        },

        dismiss(): void {
            this.entry = null;
            this.extension = null;
        },
    },
});

/**
 * @private
 */
export type InAppPurchasesStore = ReturnType<typeof inAppPurchaseCheckoutStore>;
