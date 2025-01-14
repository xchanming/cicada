import CMS from '../../constant/sw-cms.constant';

/**
 * @private
 * @package discovery
 */
Cicada.Component.register('sw-cms-el-preview-sidebar-filter', () => import('./preview'));
/**
 * @private
 * @package discovery
 */
Cicada.Component.register('sw-cms-el-config-sidebar-filter', () => import('./config'));
/**
 * @private
 * @package discovery
 */
Cicada.Component.register('sw-cms-el-sidebar-filter', () => import('./component'));

/**
 * @private
 * @package discovery
 */
Cicada.Service('cmsService').registerCmsElement({
    name: 'sidebar-filter',
    label: 'sw-cms.elements.sidebarFilter.label',
    component: 'sw-cms-el-sidebar-filter',
    configComponent: 'sw-cms-el-config-sidebar-filter',
    previewComponent: 'sw-cms-el-preview-sidebar-filter',
    allowedPageTypes: [CMS.PAGE_TYPES.LISTING],
    disabledConfigInfoTextKey: 'sw-cms.elements.sidebarFilter.infoText.filterElement',
});
