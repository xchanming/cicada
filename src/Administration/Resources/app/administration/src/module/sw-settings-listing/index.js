/**
 * @sw-package inventory
 */
const { Module } = Cicada;

/* eslint-disable max-len, sw-deprecation-rules/private-feature-declarations */
Cicada.Component.register('sw-settings-listing', () => import('./page/sw-settings-listing'));
Cicada.Component.register('sw-settings-listing-option-base', () => import('./page/sw-settings-listing-option-base'));
Cicada.Component.extend(
    'sw-settings-listing-option-create',
    'sw-settings-listing-option-base',
    () => import('./page/sw-settings-listing-option-create'),
);
Cicada.Component.register(
    'sw-settings-listing-default-sales-channel',
    () => import('./component/sw-settings-listing-default-sales-channel'),
);
Cicada.Component.register('sw-settings-listing-delete-modal', () => import('./component/sw-settings-listing-delete-modal'));
Cicada.Component.register(
    'sw-settings-listing-option-general-info',
    () => import('./component/sw-settings-listing-option-general-info'),
);
Cicada.Component.register(
    'sw-settings-listing-option-criteria-grid',
    () => import('./component/sw-settings-listing-option-criteria-grid'),
);
Cicada.Component.register(
    'sw-settings-listing-visibility-detail',
    () => import('./component/sw-settings-listing-visibility-detail'),
);
/* eslint-enable max-len, sw-deprecation-rules/private-feature-declarations */

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
Module.register('sw-settings-listing', {
    type: 'core',
    name: 'settings-listing',
    title: 'sw-settings-listing.general.mainMenuItemGeneral',
    description: 'sw-settings-listing.general.description',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'regular-cog',
    favicon: 'icon-module-settings.png',
    entity: 'store_settings',

    routes: {
        index: {
            component: 'sw-settings-listing',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'system.system_config',
            },
        },

        edit: {
            component: 'sw-settings-listing-option-base',
            path: 'edit/:id',
            meta: {
                parentPath: 'sw.settings.listing.index',
                privilege: 'system.system_config',
            },
        },

        create: {
            component: 'sw-settings-listing-option-create',
            path: 'create',
            meta: {
                parentPath: 'sw.settings.listing.index',
                privilege: 'system.system_config',
            },
        },
    },

    settingsItem: {
        group: 'shop',
        to: 'sw.settings.listing.index',
        icon: 'regular-products',
        privilege: 'system.system_config',
    },
});
