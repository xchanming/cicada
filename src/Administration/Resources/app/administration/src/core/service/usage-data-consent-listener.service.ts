import type { LoginService } from './login.service';

/**
 * @package data-services
 *
 * @private
 */
export default function addUsageDataConsentListener(loginService: LoginService, serviceContainer: ServiceContainer) {
    loginService.addOnLoginListener(fetchUsageDataConsent);
    loginService.addOnLogoutListener(resetUsageDataConsent);

    async function fetchUsageDataConsent() {
        try {
            const consent = await serviceContainer.usageDataService.getConsent();

            Cicada.State.commit('usageData/updateConsent', consent);
        } catch {
            resetUsageDataConsent();
        }
    }

    function resetUsageDataConsent() {
        Cicada.State.commit('usageData/resetConsent');
    }
}
