import './page/sw-settings-cicada-updates-wizard';
import './page/sw-settings-cicada-updates-index';
import './view/sw-settings-cicada-updates-info';
import './view/sw-settings-cicada-updates-requirements';
import './view/sw-settings-cicada-updates-plugins';
import './acl';

const { Module } = Cicada;

/**
 * @private
 */
Module.register('sw-settings-cicada-updates', {
    type: 'core',
    name: 'settings-cicada-updates',
    title: 'sw-settings-cicada-updates.general.emptyTitle',
    description: 'sw-settings-cicada-updates.general.emptyTitle',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'regular-cog',
    favicon: 'icon-module-settings.png',

    routes: {
        wizard: {
            component: 'sw-settings-cicada-updates-wizard',
            path: 'wizard',
            meta: {
                parentPath: 'sw.settings.index.system',
                privilege: 'system.core_update',
            },
        },
    },

    settingsItem: {
        privilege: 'system.core_update',
        group: 'system',
        to: 'sw.settings.cicada.updates.wizard',
        icon: 'regular-sync',
    },
});
