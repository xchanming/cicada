import BulkEditApiFactory from '../service/bulk-edit.api.factory';

/**
 * @sw-package inventory
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Cicada.Service().register('bulkEditApiFactory', () => {
    return new BulkEditApiFactory();
});
