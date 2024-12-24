/**
 * @package data-services
 */
import initializeUsageDataContext from 'src/app/init-post/usage-data.init';

describe('src/app/init-post/usage-data.init.ts', () => {
    let isLoggedIn = true;
    let isSuccessful = true;

    beforeAll(() => {
        Cicada.Service().register('loginService', () => {
            return {
                isLoggedIn: () => isLoggedIn,
            };
        });

        Cicada.Service().register('usageDataService', () => {
            return {
                getConsent: () => {
                    if (isSuccessful) {
                        return Promise.resolve({
                            isConsentGiven: true,
                            isBannerHidden: false,
                        });
                    }

                    return Promise.reject();
                },
            };
        });
    });

    beforeEach(() => {
        Cicada.State.commit('usageData/updateConsent', {
            isConsentGiven: undefined,
            isBannerHidden: undefined,
        });
        isLoggedIn = true;
    });

    it('should set static consent data when user is not logged in', async () => {
        isLoggedIn = false;

        await initializeUsageDataContext();

        expect(Cicada.State.get('usageData').isConsentGiven).toBe(false);
        expect(Cicada.State.get('usageData').isBannerHidden).toBe(true);
    });

    it('should init the consent data when user is logged in', async () => {
        await initializeUsageDataContext();

        expect(Cicada.State.get('usageData').isConsentGiven).toBe(true);
        expect(Cicada.State.get('usageData').isBannerHidden).toBe(false);
    });

    it('should set static consent data when the request fails', async () => {
        isSuccessful = false;

        await initializeUsageDataContext();

        expect(Cicada.State.get('usageData').isConsentGiven).toBe(false);
        expect(Cicada.State.get('usageData').isBannerHidden).toBe(true);
    });
});
