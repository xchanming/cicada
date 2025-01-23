/**
 * @private
 * @sw-package discovery
 */
Cicada.Component.register('sw-cms-preview-youtube-video', () => import('./preview'));
/**
 * @private
 * @sw-package discovery
 */
Cicada.Component.register('sw-cms-block-youtube-video', () => import('./component'));

/**
 * @private
 * @sw-package discovery
 */
Cicada.Service('cmsService').registerCmsBlock({
    name: 'youtube-video',
    label: 'sw-cms.blocks.video.youtubeVideo.label',
    category: 'video',
    component: 'sw-cms-block-youtube-video',
    previewComponent: 'sw-cms-preview-youtube-video',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed',
    },
    slots: {
        video: 'youtube-video',
    },
});
