/**
 * @sw-package inventory
 */
import ProductIndexService from '../service/productIndex.api.service';
import LiveSearchApiService from '../service/livesearch.api.service';
import ExcludedSearchTermService from '../../../core/service/api/excludedSearchTerm.api.service';

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Cicada.Service().register('productIndexService', () => {
    return new ProductIndexService(Cicada.Application.getContainer('init').httpClient, Cicada.Service('loginService'));
});

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Cicada.Service().register('liveSearchService', () => {
    return new LiveSearchApiService(Cicada.Application.getContainer('init').httpClient, Cicada.Service('loginService'));
});

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Cicada.Service().register('excludedSearchTermService', () => {
    return new ExcludedSearchTermService(Cicada.Application.getContainer('init').httpClient, Cicada.Service('loginService'));
});
