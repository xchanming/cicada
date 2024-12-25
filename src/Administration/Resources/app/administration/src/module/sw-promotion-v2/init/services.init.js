/**
 * @package buyers-experience
 */
import PromotionCodeApiService from '../service/promotion-code.api.service';

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Cicada.Service().register('promotionCodeApiService', () => {
    return new PromotionCodeApiService(Cicada.Application.getContainer('init').httpClient, Cicada.Service('loginService'));
});
