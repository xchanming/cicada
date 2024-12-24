/**
 * @package data-services
 *
 * @private
 */
export default function initUsageData(): Promise<void> {
    return new Promise<void>((resolve) => {
        const loginService = Cicada.Service('loginService');
        const usageDataApiService = Cicada.Service('usageDataService');

        if (!loginService.isLoggedIn()) {
            Cicada.State.commit('usageData/resetConsent');

            resolve();

            return;
        }

        usageDataApiService
            .getConsent()
            .then((usageData) => {
                Cicada.State.commit('usageData/updateConsent', usageData);
            })
            .catch(() => {
                Cicada.State.commit('usageData/resetConsent');
            })
            .finally(() => {
                resolve();
            });
    });
}
