/**
 * @package buyers-experience
 */
import 'src/module/sw-cms/service/cms.service';
import './index';

describe('src/module/sw-cms/elements/html/index.ts', () => {
    it('should register components correctly', () => {
        expect(Cicada.Component.getComponentRegistry().has('sw-cms-el-html')).toBe(true);
        expect(Cicada.Component.getComponentRegistry().has('sw-cms-el-preview-html')).toBe(true);
        expect(Cicada.Component.getComponentRegistry().has('sw-cms-el-config-html')).toBe(true);
        expect(Object.keys(Cicada.Service('cmsService').getCmsElementRegistry())).toContain('html');
    });
});
