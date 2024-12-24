/**
 * @package services-settings
 */
import './init/services.init';

/* eslint-disable max-len, sw-deprecation-rules/private-feature-declarations */
Cicada.Component.register('sw-bulk-edit-product', () => import('./page/sw-bulk-edit-product'));
Cicada.Component.register('sw-bulk-edit-order', () => import('./page/sw-bulk-edit-order'));
Cicada.Component.register('sw-bulk-edit-customer', () => import('./page/sw-bulk-edit-customer'));
Cicada.Component.register(
    'sw-bulk-edit-order-documents',
    () => import('./component/sw-bulk-edit-order/sw-bulk-edit-order-documents'),
);
Cicada.Component.register(
    'sw-bulk-edit-order-documents-generate-invoice',
    () => import('./component/sw-bulk-edit-order/sw-bulk-edit-order-documents-generate-invoice'),
);
Cicada.Component.extend(
    'sw-bulk-edit-order-documents-generate-cancellation-invoice',
    'sw-bulk-edit-order-documents-generate-invoice',
    () => import('./component/sw-bulk-edit-order/sw-bulk-edit-order-documents-generate-cancellation-invoice'),
);
Cicada.Component.extend(
    'sw-bulk-edit-order-documents-generate-delivery-note',
    'sw-bulk-edit-order-documents-generate-invoice',
    () => import('./component/sw-bulk-edit-order/sw-bulk-edit-order-documents-generate-delivery-note'),
);
Cicada.Component.extend(
    'sw-bulk-edit-order-documents-generate-credit-note',
    'sw-bulk-edit-order-documents-generate-invoice',
    () => import('./component/sw-bulk-edit-order/sw-bulk-edit-order-documents-generate-credit-note'),
);
Cicada.Component.register(
    'sw-bulk-edit-order-documents-download-documents',
    () => import('./component/sw-bulk-edit-order/sw-bulk-edit-order-documents-download-documents'),
);
Cicada.Component.extend(
    'sw-bulk-edit-custom-fields',
    'sw-custom-field-set-renderer',
    () => import('./component/sw-bulk-edit-custom-fields'),
);
Cicada.Component.register('sw-bulk-edit-change-type', () => import('./component/sw-bulk-edit-change-type'));
Cicada.Component.register(
    'sw-bulk-edit-change-type-field-renderer',
    () => import('./component/sw-bulk-edit-change-type-field-renderer'),
);
Cicada.Component.extend(
    'sw-bulk-edit-form-field-renderer',
    'sw-form-field-renderer',
    () => import('./component/sw-bulk-edit-form-field-renderer'),
);
Cicada.Component.register(
    'sw-bulk-edit-product-visibility',
    () => import('./component/product/sw-bulk-edit-product-visibility'),
);
Cicada.Component.register('sw-bulk-edit-product-media', () => import('./component/product/sw-bulk-edit-product-media'));
Cicada.Component.extend(
    'sw-bulk-edit-product-media-form',
    'sw-product-media-form',
    () => import('./component/product/sw-bulk-edit-product-media-form'),
);
Cicada.Component.extend(
    'sw-bulk-edit-product-description',
    'sw-text-editor',
    () => import('./component/product/sw-bulk-edit-product-description'),
);
Cicada.Component.register('sw-bulk-edit-save-modal', () => import('./component/sw-bulk-edit-save-modal'));
Cicada.Component.register('sw-bulk-edit-save-modal-confirm', () => import('./component/sw-bulk-edit-save-modal-confirm'));
Cicada.Component.register('sw-bulk-edit-save-modal-process', () => import('./component/sw-bulk-edit-save-modal-process'));
Cicada.Component.register('sw-bulk-edit-save-modal-success', () => import('./component/sw-bulk-edit-save-modal-success'));
Cicada.Component.register('sw-bulk-edit-save-modal-error', () => import('./component/sw-bulk-edit-save-modal-error'));
/* eslint-enable max-len, sw-deprecation-rules/private-feature-declarations */

const { Module } = Cicada;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Module.register('sw-bulk-edit', {
    type: 'core',
    name: 'bulk-edit',
    title: 'sw-bulk-edit.general.mainMenuTitle',
    description: 'sw-bulk-edit.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',

    routes: {
        product: {
            component: 'sw-bulk-edit-product',
            path: 'product/:parentId/:includesDigital',
            meta: {
                parentPath: 'sw.product.index',
            },
            children: {
                save: {
                    component: 'sw-bulk-edit-save-modal',
                    path: 'save',
                    redirect: {
                        name: 'sw.bulk.edit.product.save.confirm',
                    },
                    children: {
                        confirm: {
                            component: 'sw-bulk-edit-save-modal-confirm',
                            path: 'confirm',
                        },
                        process: {
                            component: 'sw-bulk-edit-save-modal-process',
                            path: 'process',
                        },
                        success: {
                            component: 'sw-bulk-edit-save-modal-success',
                            path: 'success',
                        },
                        error: {
                            component: 'sw-bulk-edit-save-modal-error',
                            path: 'error',
                        },
                    },
                },
            },
        },
        order: {
            component: 'sw-bulk-edit-order',
            path: 'order/:excludeDelivery',
            meta: {
                parentPath: 'sw.order.index',
            },
            children: {
                save: {
                    component: 'sw-bulk-edit-save-modal',
                    path: 'save',
                    redirect: {
                        name: 'sw.bulk.edit.order.save.confirm',
                    },
                    children: {
                        confirm: {
                            component: 'sw-bulk-edit-save-modal-confirm',
                            path: 'confirm',
                        },
                        process: {
                            component: 'sw-bulk-edit-save-modal-process',
                            path: 'process',
                        },
                        success: {
                            component: 'sw-bulk-edit-save-modal-success',
                            path: 'success',
                        },
                        error: {
                            component: 'sw-bulk-edit-save-modal-error',
                            path: 'error',
                        },
                    },
                },
            },
        },

        customer: {
            component: 'sw-bulk-edit-customer',
            path: 'customer',
            meta: {
                parentPath: 'sw.customer.index',
            },
            children: {
                save: {
                    component: 'sw-bulk-edit-save-modal',
                    path: 'save',
                    redirect: {
                        name: 'sw.bulk.edit.customer.save.confirm',
                    },
                    children: {
                        confirm: {
                            component: 'sw-bulk-edit-save-modal-confirm',
                            path: 'confirm',
                        },
                        process: {
                            component: 'sw-bulk-edit-save-modal-process',
                            path: 'process',
                        },
                        success: {
                            component: 'sw-bulk-edit-save-modal-success',
                            path: 'success',
                        },
                        error: {
                            component: 'sw-bulk-edit-save-modal-error',
                            path: 'error',
                        },
                    },
                },
            },
        },
    },
});
