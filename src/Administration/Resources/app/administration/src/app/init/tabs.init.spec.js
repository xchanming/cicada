/**
 * @sw-package framework
 */
import { createRouter, createWebHistory } from 'vue-router';
import initTabs from 'src/app/init/tabs.init';
import { ui } from '@cicada-ag/meteor-admin-sdk';

describe('src/app/init/tabs.init', () => {
    let routerMock;

    beforeAll(() => {
        global.allowedErrors.push({
            method: 'warn',
            msgCheck: (msg) => {
                if (typeof msg !== 'string') {
                    return false;
                }

                return msg.includes('No match found for location with path');
            },
        });

        // Mock component
        Cicada.Application.view.getComponent = () => ({});

        // Mock router factory
        Cicada.Application.addInitializer('router', () => {
            return new Cicada.Classes._private.RouterFactory(
                undefined,
                undefined,
                Cicada.Application.getContainer('factory').module,
            );
        });

        // Mock routes
        const routesMock = [
            {
                name: 'sw.category.index',
                path: '/sw/category/index/:id?',
                component: { template: '<div></div>' },
            },
            {
                name: 'sw.product.index',
                path: '/sw/product/index/:id',
                component: { template: '<div></div>' },
            },
            {
                name: 'sw.settings.usage.data.index',
                path: '/sw/settings/usage/data/index',
                component: { template: '<div></div>' },
            },
        ];

        // Mock for router
        routerMock = createRouter({
            history: createWebHistory(),
            routes: routesMock,
        });
        routerMock.push('/sw/category/index/eXaMpLeId');
        Cicada.Application.view.router = routerMock;

        // Add module to registry for receiving meta data
        Cicada.Module.register('sw-category', {
            type: 'core',
            name: 'category',
            title: 'sw-category.general.mainMenuItemIndex',
            description: 'sw-category.general.descriptionTextModule',
            version: '1.0.0',
            targetVersion: '1.0.0',
            color: '#57D9A3',
            icon: 'regular-products',
            favicon: 'icon-module-products.png',
            entity: 'category',
            routes: {
                index: {
                    component: 'sw-category-detail',
                    path: 'index',
                    meta: {
                        parentPath: 'sw.category.index',
                        privilege: 'category.viewer',
                    },
                },
            },
        });

        // start handler for extensionAPI
        initTabs();
    });

    it('should initialize tab extension API correctly', async () => {
        // add tab
        await ui.tabs('foo-position-id').addTabItem({
            label: 'My tab item',
            componentSectionId: 'foo-component-section-id',
        });

        // Check if value was registered correctly
        expect(Cicada.State.get('tabs').tabItems).toHaveProperty('foo-position-id');
        expect(Cicada.State.get('tabs').tabItems['foo-position-id']).toEqual([
            {
                label: 'My tab item',
                componentSectionId: 'foo-component-section-id',
            },
        ]);
    });

    it('should create correct route entry for tab item when route gets opened (dynamic route)', async () => {
        // add tab
        await ui.tabs('route-position-example-id').addTabItem({
            label: 'My tab item with route',
            componentSectionId: 'route-example-component-section-id',
        });

        // initialize view
        await Cicada.Application._resolveViewInitialized();

        // Visit the route and expect that the interceptor redirects the route
        await routerMock.push('/sw/category/index/eXaMpLeId/route-example-component-section-id');

        // Check if route was created correctly
        expect(routerMock.resolve('/sw/category/index/eXaMpLeId/route-example-component-section-id').matched[1]).toEqual(
            expect.objectContaining({
                name: 'sw.category.index.route-example-component-section-id',
                path: '/sw/category/index/:id?/route-example-component-section-id',
            }),
        );
    });

    it('should create correct route entry for tab item when route gets opened (static route)', async () => {
        // add tab
        await ui.tabs('route-position-example-id').addTabItem({
            label: 'My tab item with route',
            componentSectionId: 'route-example-component-section-id',
        });

        // initialize view
        await Cicada.Application._resolveViewInitialized();

        // Visit the route and expect that the interceptor redirects the route
        await routerMock.push('/sw/settings/usage/data/index/route-example-component-section-id');

        // Check if route was created correctly
        expect(routerMock.resolve('/sw/settings/usage/data/index/route-example-component-section-id').matched[1]).toEqual(
            expect.objectContaining({
                name: 'sw.settings.usage.data.index.route-example-component-section-id',
                path: '/sw/settings/usage/data/index/route-example-component-section-id',
            }),
        );
    });

    it('should add the correct meta data to the route (dynamic)', async () => {
        // add tab
        await ui.tabs('route-position-example-id').addTabItem({
            label: 'My tab item with route',
            componentSectionId: 'route-example-component-section-id',
        });

        // initialize view
        await Cicada.Application._resolveViewInitialized();

        // Visit the route and expect that the interceptor redirects the route
        await routerMock.push('/sw/category/index/eXaMpLeId/route-example-component-section-id');

        // Check if route was created correctly
        expect(routerMock.resolve('/sw/category/index/eXaMpLeId/route-example-component-section-id').matched[1]).toEqual(
            expect.objectContaining({
                meta: expect.objectContaining({
                    parentPath: 'sw.category.index',
                    $module: expect.objectContaining({
                        type: 'core',
                        name: 'category',
                        title: 'sw-category.general.mainMenuItemIndex',
                        description: 'sw-category.general.descriptionTextModule',
                        version: '1.0.0',
                        targetVersion: '1.0.0',
                        color: '#57D9A3',
                        icon: 'regular-products',
                        favicon: 'icon-module-products.png',
                        entity: 'category',
                    }),
                }),
            }),
        );
    });
});
