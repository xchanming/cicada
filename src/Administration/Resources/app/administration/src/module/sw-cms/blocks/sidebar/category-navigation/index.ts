/**
 * @private
 * @package buyers-experience
 */
Cicada.Component.register('sw-cms-preview-category-navigation', () => import('./preview'));
/**
 * @private
 * @package buyers-experience
 */
Cicada.Component.register('sw-cms-block-category-navigation', () => import('./component'));

/**
 * @private
 * @package buyers-experience
 */
Cicada.Service('cmsService').registerCmsBlock({
    name: 'category-navigation',
    label: 'sw-cms.blocks.sidebar.categoryNavigation.label',
    category: 'sidebar',
    component: 'sw-cms-block-category-navigation',
    previewComponent: 'sw-cms-preview-category-navigation',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed',
    },
    slots: {
        content: 'category-navigation',
    },
});
