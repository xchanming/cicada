/**
 * @sw-package framework
 */
describe('directives/click-outside', () => {
    it('should register the directive', () => {
        expect(Cicada.Directive.getByName('click-outside')).toBeDefined();
    });
});
