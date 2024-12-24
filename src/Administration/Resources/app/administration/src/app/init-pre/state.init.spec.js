/**
 * @package admin
 */
import initState from 'src/app/init-pre/state.init';

describe('src/app/init-pre/state.init.ts', () => {
    initState();

    it('should contain all state methods', () => {
        expect(Cicada.State._store).toBeDefined();
        expect(Cicada.State.list).toBeDefined();
        expect(Cicada.State.get).toBeDefined();
        expect(Cicada.State.getters).toBeDefined();
        expect(Cicada.State.commit).toBeDefined();
        expect(Cicada.State.dispatch).toBeDefined();
        expect(Cicada.State.watch).toBeDefined();
        expect(Cicada.State.subscribe).toBeDefined();
        expect(Cicada.State.subscribeAction).toBeDefined();
        expect(Cicada.State.registerModule).toBeDefined();
        expect(Cicada.State.unregisterModule).toBeDefined();
    });

    it('should initialized all state modules', () => {
        expect(Cicada.State.list()).toHaveLength(22);

        expect(Cicada.State.get('notification')).toBeDefined();
        expect(Cicada.State.get('session')).toBeDefined();
        expect(Cicada.State.get('system')).toBeDefined();
        expect(Cicada.State.get('licenseViolation')).toBeDefined();
        expect(Cicada.State.get('context')).toBeDefined();
        expect(Cicada.State.get('error')).toBeDefined();
        expect(Cicada.State.get('settingsItems')).toBeDefined();
        expect(Cicada.State.get('cicadaApps')).toBeDefined();
        expect(Cicada.State.get('extensionEntryRoutes')).toBeDefined();
        expect(Cicada.State.get('marketing')).toBeDefined();
        expect(Cicada.State.get('extensionComponentSections')).toBeDefined();
        expect(Cicada.State.get('extensions')).toBeDefined();
        expect(Cicada.State.get('tabs')).toBeDefined();
        expect(Cicada.State.get('menuItem')).toBeDefined();
        expect(Cicada.State.get('extensionSdkModules')).toBeDefined();
        expect(Cicada.State.get('modals')).toBeDefined();
        expect(Cicada.State.get('extensionMainModules')).toBeDefined();
        expect(Cicada.State.get('actionButtons')).toBeDefined();
        expect(Cicada.State.get('ruleConditionsConfig')).toBeDefined();
        expect(Cicada.State.get('sdkLocation')).toBeDefined();
        expect(Cicada.State.get('usageData')).toBeDefined();
        expect(Cicada.State.get('adminHelpCenter')).toBeDefined();
    });

    it('should be able to get cmsPageState backwards compatible', () => {
        // The cmsPageState is deprecated and causes a warning, therefore ignore it
        global.allowedErrors.push({
            method: 'warn',
            msgCheck: (_, msg) => {
                if (typeof msg !== 'string') {
                    return false;
                }

                return msg === 'Cicada.State.get("cmsPageState") is deprecated! Use Cicada.Store.get instead.';
            },
        });

        Cicada.Store.register({
            id: 'cmsPage',
            state: () => ({
                foo: 'bar',
            }),
        });

        expect(Cicada.Store.get('cmsPage').foo).toBe('bar');
        Cicada.Store.unregister('cmsPage');
    });

    it('should be able to commit cmsPageState backwards compatible', () => {
        // The cmsPageState is deprecated and causes a warning, therefore ignore it
        global.allowedErrors.push({
            method: 'warn',
            msgCheck: (_, msg) => {
                if (typeof msg !== 'string') {
                    return false;
                }

                return msg === 'Cicada.State.get("cmsPageState") is deprecated! Use Cicada.Store.get instead.';
            },
        });

        Cicada.Store.register({
            id: 'cmsPage',
            state: () => ({
                foo: 'bar',
            }),
            actions: {
                setFoo(foo) {
                    this.foo = foo;
                },
            },
        });

        const store = Cicada.Store.get('cmsPage');
        expect(store.foo).toBe('bar');

        store.setFoo('jest');
        expect(store.foo).toBe('jest');

        Cicada.Store.unregister('cmsPage');
    });
});
