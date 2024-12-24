/**
 * @package admin
 */

/* @private */
export default () => {
    /* eslint-disable sw-deprecation-rules/private-feature-declarations, max-len */
    Cicada.Component.register('sw-code-editor', () => import('src/app/asyncComponent/form/sw-code-editor'));
    Cicada.Component.register('sw-datepicker', () => import('src/app/asyncComponent/form/sw-datepicker'));
    Cicada.Component.register(
        'sw-datepicker-deprecated',
        () => import('src/app/asyncComponent/form/sw-datepicker-deprecated'),
    );
    Cicada.Component.register('sw-chart', () => import('src/app/asyncComponent/base/sw-chart'));
    Cicada.Component.register('sw-help-center-v2', () => import('src/app/asyncComponent/utils/sw-help-center'));
    Cicada.Component.register('sw-help-sidebar', () => import('src/app/asyncComponent/sidebar/sw-help-sidebar'));
    Cicada.Component.register('sw-image-slider', () => import('src/app/asyncComponent/media/sw-image-slider'));
    Cicada.Component.register(
        'sw-media-add-thumbnail-form',
        () => import('src/app/asyncComponent/media/sw-media-add-thumbnail-form'),
    );
    Cicada.Component.register('sw-media-base-item', () => import('src/app/asyncComponent/media/sw-media-base-item'));
    Cicada.Component.extend(
        'sw-media-compact-upload-v2',
        'sw-media-upload-v2',
        () => import('src/app/asyncComponent/media/sw-media-compact-upload-v2'),
    );
    Cicada.Component.register(
        'sw-media-entity-mapper',
        () => import('src/app/asyncComponent/media/sw-media-entity-mapper'),
    );
    Cicada.Component.register('sw-media-field', () => import('src/app/asyncComponent/media/sw-media-field'));
    Cicada.Component.register(
        'sw-media-folder-content',
        () => import('src/app/asyncComponent/media/sw-media-folder-content'),
    );
    Cicada.Component.register('sw-media-folder-item', () => import('src/app/asyncComponent/media/sw-media-folder-item'));
    Cicada.Component.register(
        'sw-media-list-selection-item-v2',
        () => import('src/app/asyncComponent/media/sw-media-list-selection-item-v2'),
    );
    Cicada.Component.register(
        'sw-media-list-selection-v2',
        () => import('src/app/asyncComponent/media/sw-media-list-selection-v2'),
    );
    Cicada.Component.register('sw-media-media-item', () => import('src/app/asyncComponent/media/sw-media-media-item'));
    Cicada.Component.register('sw-media-modal-delete', () => import('src/app/asyncComponent/media/sw-media-modal-delete'));
    Cicada.Component.register(
        'sw-media-modal-folder-dissolve',
        () => import('src/app/asyncComponent/media/sw-media-modal-folder-dissolve'),
    );
    Cicada.Component.register(
        'sw-media-modal-folder-settings',
        () => import('src/app/asyncComponent/media/sw-media-modal-folder-settings'),
    );
    Cicada.Component.register('sw-media-modal-move', () => import('src/app/asyncComponent/media/sw-media-modal-move'));
    Cicada.Component.register(
        'sw-media-modal-replace',
        () => import('src/app/asyncComponent/media/sw-media-modal-replace'),
    );
    Cicada.Component.register('sw-media-preview-v2', () => import('src/app/asyncComponent/media/sw-media-preview-v2'));
    Cicada.Component.extend(
        'sw-media-replace',
        'sw-media-upload-v2',
        import('src/app/asyncComponent/media/sw-media-replace'),
    );
    Cicada.Component.register('sw-media-upload-v2', () => import('src/app/asyncComponent/media/sw-media-upload-v2'));
    Cicada.Component.register('sw-media-url-form', () => import('src/app/asyncComponent/media/sw-media-url-form'));
    Cicada.Component.register('sw-sidebar-media-item', () => import('src/app/asyncComponent/media/sw-sidebar-media-item'));
    Cicada.Component.register('sw-extension-icon', () => import('src/app/asyncComponent/extension/sw-extension-icon'));
    Cicada.Component.register('sw-ai-copilot-badge', () => import('src/app/asyncComponent/feedback/sw-ai-copilot-badge'));
    Cicada.Component.register(
        'sw-ai-copilot-warning',
        () => import('src/app/asyncComponent/feedback/sw-ai-copilot-warning'),
    );
    Cicada.Component.register('sw-string-filter', () => import('src/app/asyncComponent/filter/sw-string-filter'));
    /* eslint-enable sw-deprecation-rules/private-feature-declarations, max-len */
};
