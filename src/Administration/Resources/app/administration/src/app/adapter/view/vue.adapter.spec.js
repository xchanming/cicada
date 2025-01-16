/**
 * @package admin
 */

import { shallowMount, config } from '@vue/test-utils';
import VueAdapter from 'src/app/adapter/view/vue.adapter';
import ViewAdapter from 'src/core/adapter/view.adapter';
import Bottle from 'bottlejs';
import ApplicationBootstrapper from 'src/core/application';
import LocaleFactory from 'src/core/factory/locale.factory';
import DirectiveFactory from 'src/core/factory/directive.factory';
import FilterFactory from 'src/core/factory/filter.factory';
import VueRouter from 'vue-router';
import AsyncComponentFactory from 'src/core/factory/async-component.factory';
import ModuleFactory from 'src/core/factory/module.factory';
import initializeRouter from 'src/app/init/router.init';
import setupCicadaDevtools from 'src/app/adapter/view/sw-vue-devtools';
import { h, defineComponent } from 'vue';

// Mock performance api for vue devtools
window.performance.mark = () => {};
window.performance.measure = () => {};
window.performance.clearMarks = () => {};

jest.mock('src/app/adapter/view/sw-vue-devtools', () => {
    return jest.fn();
});

Cicada.Service().register('localeHelper', () => {
    return {
        setLocaleWithId: jest.fn(),
    };
});

function createApplication() {
    // create application instance
    Bottle.config = { strict: false };
    const container = new Bottle();

    return new ApplicationBootstrapper(container);
}

describe('ASYNC app/adapter/view/vue.adapter.js', () => {
    let application;
    let vueAdapter;

    beforeEach(async () => {
        application = createApplication();

        // delete global $router and $routes mocks
        delete config.global.mocks.$router;
        delete config.global.mocks.$route;

        if (!Cicada.Service('loginService')) {
            Cicada.Service().register('loginService', () => {
                return {
                    isLoggedIn: () => true,
                };
            });
        }

        if (!Cicada.Service('localeToLanguageService')) {
            Cicada.Service().register('localeToLanguageService', () => {
                return {
                    localeToLanguage: () => Promise.resolve(),
                };
            });
        }

        Cicada.State.get('system').locales = [
            'en-GB',
            'de-DE',
        ];

        Cicada.State.commit('setAdminLocale', {
            locales: [
                'en-GB',
                'de-DE',
            ],
            locale: 'en-GB',
            languageId: '12345678',
        });

        // create vue adapter
        vueAdapter = new VueAdapter(application);

        // reset node env
        process.env.NODE_ENV = 'test';
    });

    afterEach(() => {
        AsyncComponentFactory.markComponentTemplatesAsNotResolved();
    });

    it('should be an class', async () => {
        const type = typeof VueAdapter;
        expect(type).toBe('function');
    });

    it('should extends the view adapter', async () => {
        const isInstanceOfViewAdapter = VueAdapter.prototype instanceof ViewAdapter;
        expect(isInstanceOfViewAdapter).toBeTruthy();
    });

    it('initLocales should call setLocaleFromuser', async () => {
        application = createApplication().addFactory('locale', () => {
            return LocaleFactory;
        });

        // create vueAdapter with custom application
        vueAdapter = new VueAdapter(application);

        // Mock function
        vueAdapter.setLocaleFromUser = jest.fn();

        vueAdapter.initLocales({
            subscribe: () => {},
            dispatch: () => {},
            state: { session: { currentLocale: 'en-GB' } },
        });

        expect(vueAdapter.setLocaleFromUser).toHaveBeenCalled();
    });

    it('setLocaleFromUser should not set the user when user does not exists', async () => {
        vueAdapter.setLocaleFromUser({
            state: { session: { currentUser: null } },
        });

        expect(Cicada.Service('localeHelper').setLocaleWithId).not.toHaveBeenCalled();
    });

    it('setLocaleFromUser should set the user when user does not exists', async () => {
        vueAdapter.setLocaleFromUser({
            state: { session: { currentUser: { localeId: '12345' } } },
        });

        expect(Cicada.Service('localeHelper').setLocaleWithId).toHaveBeenCalled();
    });

    it('setLocaleFromUser should call the service with the user id from the store', async () => {
        const expectedId = '12345678';

        vueAdapter.setLocaleFromUser({
            state: { session: { currentUser: { localeId: expectedId } } },
        });

        expect(Cicada.Service('localeHelper').setLocaleWithId).toHaveBeenCalledWith(expectedId);
    });

    it('initLocales should watch for user changes and recall the "setLocaleWithId"', async () => {
        application = createApplication().addFactory('locale', () => {
            return LocaleFactory;
        });

        // Mock current user in state
        Cicada.State.get('session').currentUser = {
            localeId: 'english-id',
        };

        // create vueAdapter with custom application
        vueAdapter = new VueAdapter(application);

        vueAdapter.initLocales({
            subscribe: () => {},
            dispatch: () => {},
            state: { session: { currentLocale: 'en-GB' } },
        });

        // Change the user
        Cicada.State.get('session').currentUser = {
            localeId: 'german-id',
        };

        await flushPromises();

        expect(Cicada.Service('localeHelper').setLocaleWithId).toHaveBeenCalledWith('german-id');
    });

    it('should resolve mixins by explicit Mixin get by name call', async () => {
        Cicada.Mixin.register('foo1', {
            methods: {
                fooBar() {
                    return this.title;
                },
            },
        });

        Cicada.Component.register('test-component1', {
            template: '<div></div>',
            name: 'test-component1',
            data() {
                return {
                    title: 'testComponent',
                };
            },
            mixins: [
                Cicada.Mixin.getByName('foo1'),
            ],
            methods: {
                bar() {
                    return 'bar';
                },
            },
        });

        Cicada.Component.markComponentAsSync('test-component1');
        const buildComp = await vueAdapter.createComponent('test-component1');

        const wrapper = shallowMount(await buildComp);

        expect(wrapper.vm.fooBar).toBeDefined();
        expect(wrapper.vm.bar).toBeDefined();
        expect(wrapper.vm.fooBar()).toBe('testComponent');
        expect(wrapper.vm.bar()).toBe('bar');
    });

    it('should resolve mixins by explicit Mixin get by name call with override', async () => {
        Cicada.Mixin.register('foo2', {
            methods: {
                fooBar() {
                    return this.title;
                },
            },
        });

        Cicada.Component.register('test-component2', {
            template: '<div></div>',
            name: 'test-component2',
            data() {
                return {
                    title: 'testComponent',
                };
            },
            mixins: [
                Cicada.Mixin.getByName('foo2'),
            ],
            methods: {
                bar() {
                    return 'bar';
                },
            },
        });

        Cicada.Component.override('test-component2', {
            data() {
                return {
                    title: 'testComponentOverride',
                };
            },
            methods: {
                buz() {
                    return 'buz';
                },
            },
        });

        Cicada.Component.markComponentAsSync('test-component2');
        const buildComp = await vueAdapter.createComponent('test-component2');
        const wrapper = shallowMount(await buildComp);

        expect(wrapper.vm.fooBar).toBeDefined();
        expect(wrapper.vm.bar).toBeDefined();
        expect(wrapper.vm.buz).toBeDefined();
        expect(wrapper.vm.fooBar()).toBe('testComponentOverride');
        expect(wrapper.vm.bar()).toBe('bar');
        expect(wrapper.vm.buz()).toBe('buz');
    });

    it('should resolve mixins by string', async () => {
        Cicada.Mixin.register('foo3', {
            methods: {
                fooBar() {
                    return this.title;
                },
            },
        });

        Cicada.Component.register('test-component3', {
            template: '<div></div>',
            name: 'test-component3',
            data() {
                return {
                    title: 'testComponent3',
                };
            },
            mixins: [
                'foo3',
            ],
            methods: {
                bar() {},
            },
        });

        Cicada.Component.markComponentAsSync('test-component3');
        const buildComp = await vueAdapter.createComponent('test-component3');
        const wrapper = shallowMount(await buildComp);

        expect(wrapper.vm.fooBar).toBeDefined();
        expect(wrapper.vm.bar).toBeDefined();
        expect(wrapper.vm.fooBar()).toBe('testComponent3');
    });

    it('should resolve mixins by string with override', async () => {
        Cicada.Mixin.register('foo4', {
            methods: {
                fooBar() {
                    return this.title;
                },
            },
        });

        Cicada.Component.register('test-component4', {
            template: '<div></div>',
            name: 'test-component4',
            data() {
                return {
                    title: 'testComponent4',
                };
            },
            mixins: [
                'foo4',
            ],
            methods: {
                bar() {},
            },
        });

        Cicada.Component.override('test-component4', {
            data() {
                return {
                    title: 'testComponentOverride4',
                };
            },
            methods: {
                buz() {},
            },
        });

        Cicada.Component.markComponentAsSync('test-component4');
        const buildComp = await vueAdapter.createComponent('test-component4');
        const wrapper = shallowMount(await buildComp);

        expect(wrapper.vm.fooBar).toBeDefined();
        expect(wrapper.vm.bar).toBeDefined();
        expect(wrapper.vm.buz).toBeDefined();
        expect(wrapper.vm.fooBar()).toBe('testComponentOverride4');
    });

    it('should resolve mixins for component in combination with overrides', async () => {
        Cicada.Mixin.register('foo-with-data', {
            data() {
                return {
                    sortBy: null,
                };
            },
            methods: {
                fooBar() {
                    return this.sortBy;
                },
            },
        });

        Cicada.Component.register('test-component-foobar-with-mixin', {
            template: '<div></div>',
            name: 'test-component',
            data() {
                return {
                    sortBy: 'date',
                };
            },
            mixins: [
                'foo-with-data',
            ],
            methods: {
                bar() {},
                fooBar() {
                    return this.sortBy;
                },
            },
        });

        Cicada.Component.markComponentAsSync('test-component-foobar-with-mixin');
        const buildComp = await vueAdapter.createComponent('test-component-foobar-with-mixin');
        let wrapper = shallowMount(await buildComp);

        expect(wrapper.vm.fooBar).toBeDefined();
        expect(wrapper.vm.bar).toBeDefined();
        expect(wrapper.vm.fooBar()).toBe('date');

        // add an override to the component
        Cicada.Component.override('test-component-foobar-with-mixin', {});

        Cicada.Component.markComponentAsSync('test-component-foobar-with-mixin');
        const buildOverrideComp = await vueAdapter.createComponent('test-component-foobar-with-mixin');
        wrapper = shallowMount(await buildOverrideComp);

        expect(wrapper.vm.fooBar).toBeDefined();
        expect(wrapper.vm.bar).toBeDefined();
        expect(wrapper.vm.fooBar()).toBe('date');
    });

    it('should extend mixins', async () => {
        Cicada.Mixin.register('swFoo', {
            methods: {
                fooBar() {
                    return this.title;
                },
            },
        });

        Cicada.Mixin.register('swBar', {
            methods: {
                biz() {
                    return this.title;
                },
                buz() {
                    return 'mixin';
                },
            },
        });

        Cicada.Component.register('extendable-component', {
            template: '{% block foo %}<div>aaaaa</div>{% endblock %}',
            name: 'extendable-component',
            data() {
                return {
                    title: 'testComponent',
                };
            },
            mixins: [
                'swFoo',
            ],
            methods: {
                bar() {},
            },
        });

        Cicada.Component.extend('sw-test-component-extended', 'extendable-component', {
            template: '{% block foo %}<div>bbbbb</div>{% endblock %}',
            mixins: [
                'swBar',
            ],
            data() {
                return {
                    title: 'testComponentExtended',
                };
            },
            methods: {
                buz() {
                    return 'component';
                },
            },
        });

        Cicada.Component.markComponentAsSync('sw-test-component-extended');
        const buildComp = await vueAdapter.createComponent('sw-test-component-extended');
        const wrapper = shallowMount(await buildComp);

        expect(wrapper.vm.fooBar).toBeDefined();
        expect(wrapper.vm.bar).toBeDefined();
        expect(wrapper.vm.biz).toBeDefined();
        expect(wrapper.vm.buz).toBeDefined();
        expect(wrapper.vm.fooBar()).toBe('testComponentExtended');
        expect(wrapper.vm.buz()).toBe('component');
    });

    it('should allow multi-inheritance with multiple mixins and lifecycle hooks are only executed once', async () => {
        const lifecycleSpy = jest.fn();
        Cicada.Mixin.register('first-mixin', {
            created() {
                lifecycleSpy();
            },
            methods: {
                foo() {
                    return 'foo';
                },
            },
        });

        Cicada.Mixin.register('second-mixin', {
            methods: {
                bar() {
                    return 'bar';
                },
            },
        });

        Cicada.Component.register('base-component', {
            template: '<div class="base-component"></div>',
        });

        Cicada.Component.override('base-component', {
            mixins: ['first-mixin'],
        });

        Cicada.Component.override('base-component', {
            mixins: [
                'second-mixin',
                'first-mixin',
            ],
        });

        Cicada.Component.markComponentAsSync('base-component');
        const buildComp = await vueAdapter.createComponent('base-component');
        const wrapper = shallowMount(await buildComp);

        expect(wrapper.vm.foo).toBeDefined();
        expect(wrapper.vm.bar).toBeDefined();
        expect(wrapper.vm.foo()).toBe('foo');
        expect(wrapper.vm.bar()).toBe('bar');

        expect(lifecycleSpy).toHaveBeenCalledTimes(1);
    });

    it('should build & create a vue.js component', async () => {
        const componentDefinition = {
            name: 'sw-foo',

            render() {
                return h(
                    'div',
                    {
                        class: {
                            'sw-foo': true,
                        },
                    },
                    ['Some text'],
                );
            },
        };

        const component = vueAdapter.buildAndCreateComponent(componentDefinition);
        const mountedComponent = shallowMount(component);
        expect(mountedComponent.vm).toBeTruthy();
    });

    describe('should initialize everything correctly', () => {
        let rootComponent;

        beforeAll(() => {
            global.allowedErrors.push({
                method: 'warn',
                msgCheck: (_, msg) => {
                    if (typeof msg !== 'string') {
                        return false;
                    }

                    return msg.includes('plugin is already installed');
                },
            });
        });

        beforeEach(async () => {
            process.env.NODE_ENV = 'development';

            application = createApplication()
                .addFactory('locale', () => {
                    return LocaleFactory;
                })
                .addFactory('directive', () => {
                    return DirectiveFactory;
                })
                .addFactory('filter', () => {
                    return FilterFactory;
                })
                .addFactory('component', () => {
                    return AsyncComponentFactory;
                })
                .addFactory('module', () => {
                    return ModuleFactory;
                });

            application.addInitializer('router', initializeRouter);

            const locale = Cicada.Application.getContainer('factory').locale;
            if (!locale.getLocaleByName('en-GB')) {
                locale.register('en-GB', {
                    global: {
                        'sw-admin-menu': {
                            textCicadaAdmin: 'Text Cicada Admin',
                        },
                        my: {
                            mock: {
                                title: 'Mock title',
                            },
                        },
                    },
                });
            }

            if (!Cicada.Filter.getByName('my-mock-filter')) {
                Cicada.Filter.register('my-mock-filter', () => {
                    return 'mocked';
                });
            }

            if (!Cicada.Directive.getByName('my-mock-directive')) {
                Cicada.Directive.register('my-mock-directive', () => {
                    return {
                        bind() {},
                        inserted() {},
                        update() {},
                        componentUpdated() {},
                        unbind() {},
                    };
                });
            }

            // create vueAdapter with custom application
            vueAdapter = new VueAdapter(application);

            // create router
            const router = VueRouter.createRouter({
                history: VueRouter.createWebHashHistory(),
                routes: [
                    {
                        path: '/',
                        component: defineComponent({
                            template: '<sw-admin></sw-admin>',
                        }),
                    },
                ],
            });

            // add main component
            if (!Cicada.Component.getComponentRegistry().has('sw-admin')) {
                Cicada.Component.register('sw-admin', {
                    template: '<div class="sw-admin"></div>',
                });
            }

            // add VueAdapter to Cicada object
            Cicada.Application.setViewAdapter(vueAdapter);

            await vueAdapter.initDependencies();

            // create div with id app
            document.body.innerHTML = '<div id="app"></div>';

            rootComponent = vueAdapter.init('#app', router, {});
        });

        afterEach(() => {
            rootComponent.unmount();
            rootComponent = undefined;
        });

        it('should initialize the plugins correctly', async () => {
            // check if all plugins are registered correctly
            expect(rootComponent.config.globalProperties.$router).toBeDefined();
            expect(rootComponent.config.globalProperties.$tc).toBeDefined();
            expect(rootComponent.config.globalProperties.$store).toBeDefined();
            expect(rootComponent.config.globalProperties.$dataScope).toBeDefined();
        });

        it('should initialize the directives correctly', async () => {
            expect(rootComponent._context.directives['my-mock-directive']).toBeDefined();
        });

        it('should add the createTitle to the rootComponent', () => {
            expect(rootComponent.config.globalProperties.$createTitle).toBeDefined();
        });

        it('should have correct working createTitle method', () => {
            const result = rootComponent.config.globalProperties.$createTitle.call(
                {
                    $root: {
                        $tc: (v) => rootComponent.$tc(v),
                    },
                    $route: {
                        meta: {
                            $module: {
                                title: 'global.my.mock.title',
                            },
                        },
                    },
                },
                'Test',
            );

            expect(result).toBe('Test | Mock title | Text Cicada Admin');
        });

        it('should add the store to the rootComponent', () => {
            expect(rootComponent.config.globalProperties.$store).toBeDefined();
        });

        it('should add all components to the root component', () => {
            expect(rootComponent._context.components['sw-admin']).toBeDefined();
        });

        it('should register the Meteor Components', () => {
            const meteorComponents = [
                'mt-banner',
                'mt-loader',
                'mt-progress-bar',
                'mt-button',
                'mt-checkbox',
                'mt-colorpicker',
                'mt-datepicker',
                'mt-email-field',
                'mt-external-link',
                'mt-number-field',
                'mt-password-field',
                'mt-select',
                'mt-switch',
                'mt-text-field',
                'mt-textarea',
                'mt-url-field',
                'mt-icon',
                'mt-data-table',
                'mt-pagination',
                'mt-skeleton-bar',
            ];

            meteorComponents.forEach((componentName) => {
                expect(rootComponent._context.components[componentName]).toBeDefined();
            });
        });

        it('should add the router to the rootComponent', () => {
            expect(rootComponent.config.globalProperties.$router).toBeDefined();
        });

        it('should setup the devtools in development environment', async () => {
            expect(setupCicadaDevtools).toHaveBeenCalled();
        });

        it('should return the wrapper', async () => {
            const wrapper = vueAdapter.getWrapper();
            expect(wrapper).toHaveProperty('use');
            expect(wrapper).toHaveProperty('config');
            expect(wrapper).toHaveProperty('component');
            expect(wrapper).toHaveProperty('directive');
            expect(wrapper).toHaveProperty('mount');
        });

        it('should return the adapter name', async () => {
            expect(vueAdapter.getName()).toBe('Vue.js');
        });

        it('should update the i18n global locale to update the locale in UI when the locale in the session store changes', async () => {
            const expectedLocale = 'de-DE';

            Cicada.State.commit('setAdminLocale', {
                locales: [
                    'en-GB',
                    'de-DE',
                ],
                locale: expectedLocale,
                languageId: '12345678',
            });

            expect(vueAdapter.i18n.global.locale).toEqual(expectedLocale);
        });
    });
});
