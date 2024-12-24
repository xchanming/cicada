import PaymentOverviewCardStore from '../state/overview-cards.store';

/**
 * @package checkout
 */

Cicada.State.registerModule('paymentOverviewCardState', PaymentOverviewCardStore);

Cicada.ExtensionAPI.handle('uiModulePaymentOverviewCard', (componentConfig) => {
    Cicada.State.commit('paymentOverviewCardState/add', componentConfig);
});
