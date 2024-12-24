import createHttpClient from 'src/core/factory/http.factory';
import createLoginService from 'src/core/service/login.service';
import StoreApiService from 'src/core/service/api/store.api.service';
import CicadaExtensionService from 'src/module/sw-extension/service/cicada-extension.service';
import ExtensionStoreActionService from 'src/module/sw-extension/service/extension-store-action.service';
import AppModulesService from 'src/core/service/api/app-modules.service';
import 'src/module/sw-extension/service';
import initState from 'src/module/sw-extension/store';
import appModulesFixtures from '../../../app/service/_mocks/testApps.json';

jest.mock('src/module/sw-extension/service/extension-store-action.service');
jest.mock('src/core/service/api/app-modules.service');

const httpClient = createHttpClient(Cicada.Context.api);
Cicada.Application.getContainer('init').httpClient = httpClient;
Cicada.Service().register('loginService', () => {
    return createLoginService(httpClient, Cicada.Context.api);
});

Cicada.Service().register('storeService', () => {
    return new StoreApiService(httpClient, Cicada.Service('loginService'));
});

Cicada.Service().register('cicadaDiscountCampaignService', () => {
    return { isDiscountCampaignActive: jest.fn(() => true) };
});

/**
 * @package checkout
 */
describe('src/module/sw-extension/service/cicada-extension.service', () => {
    let cicadaExtensionService;

    beforeAll(() => {
        cicadaExtensionService = Cicada.Service('cicadaExtensionService');

        initState(Cicada);

        if (Cicada.State.get('extensionEntryRoutes')) {
            Cicada.State.unregisterModule('extensionEntryRoutes');
        }
        Cicada.State.registerModule('extensionEntryRoutes', {
            namespaced: true,
            state: {
                routes: {
                    ExamplePlugin: {
                        route: 'test.foo',
                    },
                },
            },
        });
    });

    describe('it delegates lifecycle methods', () => {
        const mockedExtensionStoreActionService = new ExtensionStoreActionService(
            httpClient,
            Cicada.Service('loginService'),
        );
        mockedExtensionStoreActionService.getMyExtensions.mockImplementation(() => {
            return ['new extensions'];
        });

        const mockedModuleService = new AppModulesService(httpClient, Cicada.Service('loginService'));
        mockedModuleService.fetchAppModules.mockImplementation(() => {
            return ['new app modules'];
        });

        const mockedCicadaExtensionService = new CicadaExtensionService(
            mockedModuleService,
            mockedExtensionStoreActionService,
            Cicada.Service('cicadaDiscountCampaignService'),
            Cicada.Service('storeService'),
        );

        function expectUpdateExtensionDataCalled() {
            expect(mockedExtensionStoreActionService.refresh).toHaveBeenCalledTimes(1);
            expect(mockedExtensionStoreActionService.getMyExtensions).toHaveBeenCalledTimes(1);

            expect(Cicada.State.get('cicadaExtensions').myExtensions.data).toEqual(['new extensions']);
            expect(Cicada.State.get('cicadaExtensions').myExtensions.loading).toBe(false);

            expectUpdateModulesCalled();
        }

        function expectUpdateModulesCalled() {
            expect(mockedModuleService.fetchAppModules).toHaveBeenCalledTimes(1);

            expect(Cicada.State.get('cicadaApps').apps).toEqual([
                'new app modules',
            ]);
        }

        beforeEach(() => {
            Cicada.State.commit('cicadaExtensions/myExtensions', []);
            Cicada.State.commit('cicadaApps/setApps', []);
        });

        it.each([
            [
                'installExtension',
                [
                    'someExtension',
                    'app',
                ],
            ],
            [
                'updateExtension',
                [
                    'someExtension',
                    'app',
                    true,
                ],
            ],
            [
                'uninstallExtension',
                [
                    'someExtension',
                    'app',
                    true,
                ],
            ],
            [
                'removeExtension',
                [
                    'someExtension',
                    'app',
                ],
            ],
        ])('delegates %s correctly', async (lifecycleMethod, parameters) => {
            await mockedCicadaExtensionService[lifecycleMethod](...parameters);

            expect(mockedExtensionStoreActionService[lifecycleMethod]).toHaveBeenCalledTimes(1);
            expect(mockedExtensionStoreActionService[lifecycleMethod]).toHaveBeenCalledWith(...parameters);

            expectUpdateExtensionDataCalled();
        });

        it('delegates cancelLicense correctly', async () => {
            await mockedCicadaExtensionService.cancelLicense(5);

            expect(mockedExtensionStoreActionService.cancelLicense).toHaveBeenCalledTimes(1);
            expect(mockedExtensionStoreActionService.cancelLicense).toHaveBeenCalledWith(5);
        });

        it.each([
            ['activateExtension'],
            ['deactivateExtension'],
        ])('delegates %s correctly', async (lifecycleMethod) => {
            await mockedCicadaExtensionService[lifecycleMethod]('someExtension', 'app');

            expect(mockedExtensionStoreActionService[lifecycleMethod]).toHaveBeenCalledTimes(1);
            expect(mockedExtensionStoreActionService[lifecycleMethod]).toHaveBeenCalledWith('someExtension', 'app');

            expectUpdateModulesCalled();
        });
    });

    describe('checkLogin', () => {
        const checkLoginSpy = jest.spyOn(Cicada.Service('storeService'), 'checkLogin');

        beforeEach(() => {
            Cicada.State.commit('cicadaExtensions/setUserInfo', true);
        });

        it.each([
            [{ userInfo: { email: 'user@cicada.com' } }],
            [{ userInfo: null }],
        ])('sets login status depending on checkLogin response', async (loginResponse) => {
            checkLoginSpy.mockImplementationOnce(() => loginResponse);

            await cicadaExtensionService.checkLogin();

            expect(Cicada.State.get('cicadaExtensions').userInfo).toStrictEqual(loginResponse.userInfo);
        });

        it('sets login status to false if checkLogin request fails', async () => {
            checkLoginSpy.mockImplementationOnce(() => {
                throw new Error('something went wrong');
            });

            await cicadaExtensionService.checkLogin();

            expect(Cicada.State.get('cicadaExtensions').loginStatus).toBe(false);
            expect(Cicada.State.get('cicadaExtensions').userInfo).toBeNull();
        });
    });

    describe('isVariantDiscounted', () => {
        it('returns true if price is discounted and campaign is active', async () => {
            const variant = {
                netPrice: 100,
                discountCampaign: {
                    discountedPrice: 80,
                },
            };

            expect(cicadaExtensionService.isVariantDiscounted(variant)).toBe(true);
        });

        it('returns false if price is discounted but campaign is not active', async () => {
            const variant = {
                netPrice: 100,
                discountCampaign: {
                    discountedPrice: 80,
                },
            };

            Cicada.Service('cicadaDiscountCampaignService').isDiscountCampaignActive.mockImplementationOnce(() => false);

            expect(cicadaExtensionService.isVariantDiscounted(variant)).toBe(false);
        });

        it('returns false if variant is falsy', async () => {
            expect(cicadaExtensionService.isVariantDiscounted(null)).toBe(false);
        });

        it('returns false if variant has no discountCampaign', async () => {
            expect(cicadaExtensionService.isVariantDiscounted({})).toBe(false);
        });

        it('returns false if discounted price is net price', async () => {
            expect(
                cicadaExtensionService.isVariantDiscounted({
                    netPrice: 100,
                    discountCampaign: {
                        discountedPrice: 100,
                    },
                }),
            ).toBe(false);
        });
    });

    describe('orderVariantsByRecommendation', () => {
        it('orders variants by recommendation and discounting', async () => {
            const variants = [
                {
                    netPrice: 100,
                    discountCampaign: { netPrice: 100 },
                    type: 'rent',
                },
                {
                    netPrice: 100,
                    discountCampaign: { netPrice: 80 },
                    type: 'test',
                },
                {
                    netPrice: 100,
                    discountCampaign: { netPrice: 100 },
                    type: 'test',
                },
                {
                    netPrice: 100,
                    discountCampaign: { netPrice: 100 },
                    type: 'buy',
                },
                {
                    netPrice: 100,
                    discountCampaign: { netPrice: 10 },
                    type: 'rent',
                },
            ];

            cicadaExtensionService
                .orderVariantsByRecommendation(variants)
                .forEach((current, currentIndex, orderedVariants) => {
                    const isCurrentDiscounted = cicadaExtensionService.isVariantDiscounted(current);
                    const currentRecommendation = cicadaExtensionService.mapVariantToRecommendation(current);

                    orderedVariants.forEach((comparator, comparatorIndex) => {
                        const isComparatorDiscounted = cicadaExtensionService.isVariantDiscounted(comparator);
                        const comparatorRecommendation = cicadaExtensionService.mapVariantToRecommendation(comparator);

                        if (isCurrentDiscounted !== !isComparatorDiscounted) {
                            // discounted index is always smaller than undiscounted
                            if (isCurrentDiscounted && !isComparatorDiscounted) {
                                // eslint-disable-next-line jest/no-conditional-expect
                                expect(currentIndex).toBeLessThan(comparatorIndex);
                            }

                            if (!isCurrentDiscounted && isComparatorDiscounted) {
                                // eslint-disable-next-line jest/no-conditional-expect
                                expect(currentIndex).toBeGreaterThan(comparatorIndex);
                            }
                        } else {
                            // variants are ordered by recommendation
                            if (currentRecommendation < comparatorRecommendation) {
                                // eslint-disable-next-line jest/no-conditional-expect
                                expect(currentIndex).toBeLessThan(comparatorIndex);
                            }

                            if (currentIndex > comparatorRecommendation) {
                                // eslint-disable-next-line jest/no-conditional-expect
                                expect(currentIndex).toBeGreaterThan(comparatorIndex);
                            }
                        }
                    });
                });
        });
    });

    describe('getPriceFromVariant', () => {
        it('returns discounted price if variant is discounted', async () => {
            expect(
                cicadaExtensionService.getPriceFromVariant({
                    netPrice: 100,
                    discountCampaign: {
                        discountedPrice: 80,
                    },
                }),
            ).toBe(80);
        });

        it('returns net price if variant is not discounted', async () => {
            Cicada.Service('cicadaDiscountCampaignService').isDiscountCampaignActive.mockImplementationOnce(() => false);

            expect(
                cicadaExtensionService.getPriceFromVariant({
                    netPrice: 100,
                    discountCampaign: {
                        discountedPrice: 80,
                    },
                }),
            ).toBe(100);
        });
    });

    describe('mapVariantToRecommendation', () => {
        it.each([
            [
                'free',
                0,
            ],
            [
                'rent',
                1,
            ],
            [
                'buy',
                2,
            ],
            [
                'test',
                3,
            ],
        ])('maps variant %s to position %d', (type, expectedRecommendation) => {
            expect(cicadaExtensionService.mapVariantToRecommendation({ type })).toBe(expectedRecommendation);
        });
    });

    describe('getOpenLink', () => {
        it('returns always a open link for theme', async () => {
            const themeId = Cicada.Utils.createId();

            const responses = global.repositoryFactoryMock.responses;
            responses.addResponse({
                method: 'Post',
                url: '/search-ids/theme',
                status: 200,
                response: {
                    data: [themeId],
                },
            });

            const openLink = await cicadaExtensionService.getOpenLink({
                isTheme: true,
                type: cicadaExtensionService.EXTENSION_TYPES.APP,
                name: 'SwagExampleApp',
            });

            expect(openLink).toEqual({
                name: 'sw.theme.manager.detail',
                params: { id: themeId },
            });
        });

        it('returns valid open link for app with main module', async () => {
            Cicada.State.commit('cicadaApps/setApps', appModulesFixtures);

            expect(
                await cicadaExtensionService.getOpenLink({
                    isTheme: false,
                    type: cicadaExtensionService.EXTENSION_TYPES.APP,
                    name: 'testAppA',
                }),
            ).toEqual({
                name: 'sw.extension.module',
                params: {
                    appName: 'testAppA',
                },
            });
        });

        it('returns no open link for app without main module', async () => {
            Cicada.State.commit('cicadaApps/setApps', appModulesFixtures);

            expect(
                await cicadaExtensionService.getOpenLink({
                    isTheme: false,
                    type: cicadaExtensionService.EXTENSION_TYPES.APP,
                    name: 'testAppB',
                }),
            ).toBeNull();
        });

        it('returns no open link if app can not be found', async () => {
            Cicada.State.commit('cicadaApps/setApps', appModulesFixtures);

            expect(
                await cicadaExtensionService.getOpenLink({
                    isTheme: false,
                    type: cicadaExtensionService.EXTENSION_TYPES.APP,
                    name: 'ThisAppDoesNotExist',
                }),
            ).toBeNull();
        });

        it('returns no open link for plugins not registered', async () => {
            expect(
                await cicadaExtensionService.getOpenLink({
                    isTheme: false,
                    type: cicadaExtensionService.EXTENSION_TYPES.PLUGIN,
                    name: 'SwagNoModule',
                }),
            ).toBeNull();
        });

        it('returns route for plugins registered', async () => {
            expect(
                await cicadaExtensionService.getOpenLink({
                    isTheme: false,
                    type: cicadaExtensionService.EXTENSION_TYPES.PLUGIN,
                    name: 'ExamplePlugin',
                    active: true,
                }),
            ).toEqual({
                label: null,
                name: 'test.foo',
            });
        });
    });
});
