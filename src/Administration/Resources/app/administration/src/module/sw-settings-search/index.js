/**
 * @sw-package inventory
 */
import './init/services.init';
import './acl';

const { Module } = Cicada;

/* eslint-disable max-len, sw-deprecation-rules/private-feature-declarations */
Cicada.Component.register('sw-settings-search', () => import('./page/sw-settings-search'));
Cicada.Component.register('sw-settings-search-view-general', () => import('./view/sw-settings-search-view-general'));
Cicada.Component.register('sw-settings-search-view-live-search', () => import('./view/sw-settings-search-view-live-search'));
Cicada.Component.register(
    'sw-settings-search-search-behaviour',
    () => import('./component/sw-settings-search-search-behaviour'),
);
Cicada.Component.register(
    'sw-settings-search-searchable-content',
    () => import('./component/sw-settings-search-searchable-content'),
);
Cicada.Component.register('sw-settings-search-example-modal', () => import('./component/sw-settings-search-example-modal'));
Cicada.Component.register(
    'sw-settings-search-searchable-content-general',
    () => import('./component/sw-settings-search-searchable-content-general'),
);
Cicada.Component.register(
    'sw-settings-search-searchable-content-customfields',
    () => import('./component/sw-settings-search-searchable-content-customfields'),
);
Cicada.Component.register(
    'sw-settings-search-excluded-search-terms',
    () => import('./component/sw-settings-search-excluded-search-terms'),
);
Cicada.Component.register('sw-settings-search-search-index', () => import('./component/sw-settings-search-search-index'));
Cicada.Component.register('sw-settings-search-live-search', () => import('./component/sw-settings-search-live-search'));
Cicada.Component.register(
    'sw-settings-search-live-search-keyword',
    () => import('./component/sw-settings-search-live-search-keyword'),
);
/* eslint-enable max-len, sw-deprecation-rules/private-feature-declarations */

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Module.register('sw-settings-search', {
    type: 'core',
    name: 'settings-product-search-config',
    title: 'sw-settings-search.general.mainMenuItemGeneral',
    description: 'sw-settings-snippet.general.description',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'regular-cog',
    favicon: 'icon-module-settings.png',
    entity: 'product_search_config',

    routes: {
        index: {
            component: 'sw-settings-search',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'product_search_config.viewer',
            },

            redirect: {
                name: 'sw.settings.search.index.general',
            },

            children: {
                general: {
                    component: 'sw-settings-search-view-general',
                    path: 'general',
                    meta: {
                        parentPath: 'sw.settings.index',
                        privilege: 'product_search_config.viewer',
                    },
                },

                liveSearch: {
                    component: 'sw-settings-search-view-live-search',
                    path: 'live-search',
                    meta: {
                        parentPath: 'sw.settings.index',
                        privilege: 'product_search_config.viewer',
                    },
                },
            },
        },
    },

    settingsItem: {
        group: 'shop',
        to: 'sw.settings.search.index',
        icon: 'regular-search',
        privilege: 'product_search_config.viewer',
    },
});
