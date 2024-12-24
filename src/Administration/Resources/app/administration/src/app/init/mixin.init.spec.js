/**
 * @package admin
 */
import createAppMixin from 'src/app/init/mixin.init';

describe('src/app/init/mixin.init.js', () => {
    it('should register all app mixins', () => {
        createAppMixin();

        expect(Cicada.Mixin.getByName('sw-form-field')).toBeDefined();
        expect(Cicada.Mixin.getByName('generic-condition')).toBeDefined();
        expect(Cicada.Mixin.getByName('listing')).toBeDefined();
        expect(Cicada.Mixin.getByName('notification')).toBeDefined();
        expect(Cicada.Mixin.getByName('placeholder')).toBeDefined();
        expect(Cicada.Mixin.getByName('position')).toBeDefined();
        expect(Cicada.Mixin.getByName('remove-api-error')).toBeDefined();
        expect(Cicada.Mixin.getByName('ruleContainer')).toBeDefined();
        expect(Cicada.Mixin.getByName('salutation')).toBeDefined();
        expect(Cicada.Mixin.getByName('sw-inline-snippet')).toBeDefined();
        expect(Cicada.Mixin.getByName('user-settings')).toBeDefined();
        expect(Cicada.Mixin.getByName('validation')).toBeDefined();
    });
});
