import './acl';

import './mixin/cart-notification.mixin';

import defaultSearchConfiguration from './default-search-configuration';

/**
 * @sw-package checkout
 */

const { Module } = Cicada;

/* eslint-disable max-len, sw-deprecation-rules/private-feature-declarations */
Cicada.Component.register('sw-order-list', () => import('./page/sw-order-list'));
Cicada.Component.register('sw-order-detail', () => import('./page/sw-order-detail'));
Cicada.Component.register('sw-order-create', () => import('./page/sw-order-create'));
Cicada.Component.register('sw-order-detail-general', () => import('./view/sw-order-detail-general'));
Cicada.Component.register('sw-order-detail-details', () => import('./view/sw-order-detail-details'));
Cicada.Component.register('sw-order-create-base', () => import('./view/sw-order-create-base'));
Cicada.Component.register('sw-order-create-initial', () => import('./view/sw-order-create-initial'));
Cicada.Component.register('sw-order-create-general', () => import('./view/sw-order-create-general'));
Cicada.Component.register('sw-order-create-details', () => import('./view/sw-order-create-details'));
Cicada.Component.register('sw-order-nested-line-items-modal', () => import('./component/sw-order-nested-line-items-modal'));
Cicada.Component.register('sw-order-nested-line-items-row', () => import('./component/sw-order-nested-line-items-row'));
Cicada.Component.register('sw-order-line-items-grid', () => import('./component/sw-order-line-items-grid'));
Cicada.Component.register(
    'sw-order-line-items-grid-sales-channel',
    () => import('./component/sw-order-line-items-grid-sales-channel'),
);
Cicada.Component.register('sw-order-delivery-metadata', () => import('./component/sw-order-delivery-metadata'));
Cicada.Component.register('sw-order-customer-comment', () => import('./component/sw-order-customer-comment'));
Cicada.Component.register('sw-order-product-select', () => import('./component/sw-order-product-select'));
Cicada.Component.register('sw-order-saveable-field', () => import('./component/sw-order-saveable-field'));
Cicada.Component.register('sw-order-address-modal', () => import('./component/sw-order-address-modal'));
Cicada.Component.register('sw-order-address-selection', () => import('./component/sw-order-address-selection'));
Cicada.Component.register('sw-order-leave-page-modal', () => import('./component/sw-order-leave-page-modal'));
Cicada.Component.register('sw-order-state-history-card', () => import('./component/sw-order-state-history-card'));
Cicada.Component.register(
    'sw-order-state-history-card-entry',
    () => import('./component/sw-order-state-history-card-entry'),
);
Cicada.Component.register('sw-order-state-history-modal', () => import('./component/sw-order-state-history-modal'));
Cicada.Component.register('sw-order-state-change-modal', () => import('./component/sw-order-state-change-modal'));
Cicada.Component.register('sw-order-state-select-v2', () => import('./component/sw-order-state-select-v2'));
Cicada.Component.register('sw-order-details-state-card', () => import('./component/sw-order-details-state-card'));
Cicada.Component.register('sw-order-inline-field', () => import('./component/sw-order-inline-field'));
Cicada.Component.register('sw-order-user-card', () => import('./component/sw-order-user-card'));
Cicada.Component.register('sw-order-create-details-header', () => import('./component/sw-order-create-details-header'));
Cicada.Component.register('sw-order-create-details-body', () => import('./component/sw-order-create-details-body'));
Cicada.Component.register('sw-order-create-details-footer', () => import('./component/sw-order-create-details-footer'));
Cicada.Component.register('sw-order-create-address-modal', () => import('./component/sw-order-create-address-modal'));
Cicada.Component.register('sw-order-new-customer-modal', () => import('./component/sw-order-new-customer-modal'));
Cicada.Component.register('sw-order-promotion-field', () => import('./component/sw-order-promotion-field'));
Cicada.Component.extend(
    'sw-order-promotion-tag-field',
    'sw-tagged-field',
    () => import('./component/sw-order-promotion-tag-field'),
);
Cicada.Component.register(
    'sw-order-create-invalid-promotion-modal',
    () => import('./component/sw-order-create-invalid-promotion-modal'),
);
Cicada.Component.register('sw-order-create-promotion-modal', () => import('./component/sw-order-create-promotion-modal'));
Cicada.Component.register('sw-order-create-general-info', () => import('./component/sw-order-create-general-info'));
Cicada.Component.register('sw-order-general-info', () => import('./component/sw-order-general-info'));
Cicada.Component.register('sw-order-create-initial-modal', () => import('./component/sw-order-create-initial-modal'));
Cicada.Component.register('sw-order-customer-grid', () => import('./component/sw-order-customer-grid'));
Cicada.Component.register('sw-order-create-options', () => import('./component/sw-order-create-options'));
Cicada.Component.register('sw-order-customer-address-select', () => import('./component/sw-order-customer-address-select'));
/* eslint-enable max-len, sw-deprecation-rules/private-feature-declarations */

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Module.register('sw-order', {
    type: 'core',
    name: 'order',
    title: 'sw-order.general.mainMenuItemGeneral',
    description: 'sw-order.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#A092F0',
    icon: 'regular-shopping-bag',
    favicon: 'icon-module-orders.png',
    entity: 'order',

    routes: {
        index: {
            components: {
                default: 'sw-order-list',
            },
            path: 'index',
            meta: {
                privilege: 'order.viewer',
                appSystem: {
                    view: 'list',
                },
            },
        },

        create: {
            component: 'sw-order-create',
            path: 'create',
            redirect: {
                name: 'sw.order.create.initial',
            },
            meta: {
                privilege: 'order.creator',
            },
            children: orderCreateChildren(),
        },

        detail: {
            component: 'sw-order-detail',
            path: 'detail/:id',
            redirect: {
                name: 'sw.order.detail.general',
            },
            meta: {
                privilege: 'order.viewer',
                appSystem: {
                    view: 'detail',
                },
            },
            children: orderDetailChildren(),
            props: {
                default: ($route) => {
                    return { orderId: $route.params.id };
                },
            },
        },
    },

    navigation: [
        {
            id: 'sw-order',
            label: 'sw-order.general.mainMenuItemGeneral',
            color: '#A092F0',
            icon: 'regular-shopping-bag',
            position: 30,
            privilege: 'order.viewer',
        },
        {
            path: 'sw.order.index',
            label: 'sw-order.general.mainMenuItemList',
            parent: 'sw-order',
            privilege: 'order.viewer',
        },
    ],

    defaultSearchConfiguration,
});

function orderDetailChildren() {
    return {
        general: {
            component: 'sw-order-detail-general',
            path: 'general',
            meta: {
                parentPath: 'sw.order.index',
                privilege: 'order.viewer',
            },
        },
        details: {
            component: 'sw-order-detail-details',
            path: 'details',
            meta: {
                parentPath: 'sw.order.index',
                privilege: 'order.viewer',
            },
        },
        documents: {
            component: 'sw-order-detail-documents',
            path: 'documents',
            meta: {
                parentPath: 'sw.order.index',
                privilege: 'order.viewer',
            },
        },
    };
}

function orderCreateChildren() {
    return {
        initial: {
            component: 'sw-order-create-initial',
            path: 'initial',
            meta: {
                parentPath: 'sw.order.index',
                privilege: 'order.creator',
            },
        },
        general: {
            component: 'sw-order-create-general',
            path: 'general',
            meta: {
                parentPath: 'sw.order.index',
                privilege: 'order.creator',
            },
        },
        details: {
            component: 'sw-order-create-details',
            path: 'details',
            meta: {
                parentPath: 'sw.order.index',
                privilege: 'order.creator',
            },
        },
    };
}
