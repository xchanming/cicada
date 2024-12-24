/**
 * @private
 * @package buyers-experience
 */
Cicada.Component.register('sw-cms-preview-text', () => import('./preview'));
/**
 * @private
 * @package buyers-experience
 */
Cicada.Component.register('sw-cms-block-text', () => import('./component'));

/**
 * @private
 * @package buyers-experience
 */
Cicada.Service('cmsService').registerCmsBlock({
    name: 'text',
    label: 'sw-cms.blocks.text.text.label',
    category: 'text',
    component: 'sw-cms-block-text',
    previewComponent: 'sw-cms-preview-text',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed',
    },
    slots: {
        content: 'text',
    },
});
