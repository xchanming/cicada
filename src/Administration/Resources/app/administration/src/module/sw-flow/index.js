import './service';
import './acl';

import flowState from './state/flow.state';

const { Module, State } = Cicada;
State.registerModule('swFlowState', flowState);

/* eslint-disable max-len, sw-deprecation-rules/private-feature-declarations */
Cicada.Component.register('sw-flow-index', () => import('./page/sw-flow-index'));
Cicada.Component.register('sw-flow-detail', () => import('./page/sw-flow-detail'));
Cicada.Component.register('sw-flow-detail-flow', () => import('./view/detail/sw-flow-detail-flow'));
Cicada.Component.register('sw-flow-sequence-modal', () => import('./component/sw-flow-sequence-modal'));
Cicada.Component.register('sw-flow-detail-general', () => import('./view/detail/sw-flow-detail-general'));
Cicada.Component.register('sw-flow-list', () => import('./view/listing/sw-flow-list'));
Cicada.Component.register('sw-flow-list-flow-templates', () => import('./view/listing/sw-flow-list-flow-templates'));
Cicada.Component.register('sw-flow-trigger', () => import('./component/sw-flow-trigger'));
Cicada.Component.register('sw-flow-sequence', () => import('./component/sw-flow-sequence'));
Cicada.Component.register('sw-flow-sequence-action', () => import('./component/sw-flow-sequence-action'));
Cicada.Component.register('sw-flow-sequence-condition', () => import('./component/sw-flow-sequence-condition'));
Cicada.Component.register('sw-flow-sequence-selector', () => import('./component/sw-flow-sequence-selector'));
Cicada.Component.register('sw-flow-sequence-action-error', () => import('./component/sw-flow-sequence-action-error'));
Cicada.Component.register('sw-flow-rule-modal', () => import('./component/modals/sw-flow-rule-modal'));
Cicada.Component.register('sw-flow-tag-modal', () => import('./component/modals/sw-flow-tag-modal'));
Cicada.Component.register('sw-flow-set-order-state-modal', () => import('./component/modals/sw-flow-set-order-state-modal'));
Cicada.Component.register(
    'sw-flow-grant-download-access-modal',
    () => import('./component/modals/sw-flow-grant-download-access-modal'),
);
Cicada.Component.register('sw-flow-mail-send-modal', () => import('./component/modals/sw-flow-mail-send-modal'));
Cicada.Component.register(
    'sw-flow-create-mail-template-modal',
    () => import('./component/modals/sw-flow-create-mail-template-modal'),
);
Cicada.Component.register(
    'sw-flow-event-change-confirm-modal',
    () => import('./component/modals/sw-flow-event-change-confirm-modal'),
);
Cicada.Component.register(
    'sw-flow-change-customer-group-modal',
    () => import('./component/modals/sw-flow-change-customer-group-modal'),
);
Cicada.Component.register(
    'sw-flow-change-customer-status-modal',
    () => import('./component/modals/sw-flow-change-customer-status-modal'),
);
Cicada.Component.register(
    'sw-flow-set-entity-custom-field-modal',
    () => import('./component/modals/sw-flow-set-entity-custom-field-modal'),
);
Cicada.Component.register(
    'sw-flow-affiliate-and-campaign-code-modal',
    () => import('./component/modals/sw-flow-affiliate-and-campaign-code-modal'),
);
Cicada.Component.register('sw-flow-app-action-modal', () => import('./component/modals/sw-flow-app-action-modal'));
Cicada.Component.register('sw-flow-leave-page-modal', () => import('./component/modals/sw-flow-leave-page-modal'));
/* eslint-enable max-len, sw-deprecation-rules/private-feature-declarations */

/**
 * @private
 * @sw-package after-sales
 */
Module.register('sw-flow', {
    type: 'core',
    name: 'flow',
    title: 'sw-flow.general.mainMenuItemGeneral',
    description: 'sw-flow.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'regular-cog',
    favicon: 'icon-module-settings.png',
    entity: 'flow',

    routes: {
        index: {
            component: 'sw-flow-index',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'flow.viewer',
            },
            redirect: {
                name: 'sw.flow.index.flows',
            },
            children: {
                flows: {
                    component: 'sw-flow-list',
                    path: 'flows',
                    meta: {
                        parentPath: 'sw.settings.index',
                        privilege: 'flow.viewer',
                    },
                },
                templates: {
                    component: 'sw-flow-list-flow-templates',
                    path: 'templates',
                    meta: {
                        parentPath: 'sw.settings.index',
                        privilege: 'flow.viewer',
                    },
                },
            },
        },
        detail: {
            component: 'sw-flow-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'sw.flow.index',
                privilege: 'flow.viewer',
            },
            props: {
                default(route) {
                    return {
                        flowId: route.params.id,
                    };
                },
            },
            redirect: {
                name: 'sw.flow.detail.general',
            },
            children: {
                general: {
                    component: 'sw-flow-detail-general',
                    path: 'general',
                    meta: {
                        parentPath: 'sw.flow.index',
                        privilege: 'flow.viewer',
                    },
                },
                flow: {
                    component: 'sw-flow-detail-flow',
                    path: 'flow',
                    meta: {
                        parentPath: 'sw.flow.index',
                        privilege: 'flow.viewer',
                    },
                },
            },
        },
        create: {
            component: 'sw-flow-detail',
            path: 'create/:flowTemplateId?',
            meta: {
                parentPath: 'sw.flow.index',
                privilege: 'flow.creator',
            },
            redirect: {
                name: 'sw.flow.create.general',
            },
            children: {
                general: {
                    component: 'sw-flow-detail-general',
                    path: 'general',
                    meta: {
                        parentPath: 'sw.flow.index',
                        privilege: 'flow.viewer',
                    },
                },
                flow: {
                    component: 'sw-flow-detail-flow',
                    path: 'flow',
                    meta: {
                        parentPath: 'sw.flow.index',
                        privilege: 'flow.viewer',
                    },
                },
            },
        },
    },

    settingsItem: {
        group: 'shop',
        to: 'sw.flow.index',
        icon: 'regular-flow',
        privilege: 'flow.viewer',
    },
});
