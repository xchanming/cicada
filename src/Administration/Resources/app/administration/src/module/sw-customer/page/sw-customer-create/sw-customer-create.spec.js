import { mount } from '@vue/test-utils';

/**
 * @sw-package checkout
 */

const { Context } = Cicada;
const { EntityCollection } = Cicada.Data;

async function createWrapper() {
    return mount(await wrapTestComponent('sw-customer-create', { sync: true }), {
        global: {
            stubs: {
                'sw-page': {
                    template: `
    <div>
        <slot name="smart-bar-actions"></slot>
        <slot name="content"></slot>
    </div>`,
                },
                'sw-card': true,
                'sw-language-switch': true,
                'sw-customer-address-form': true,
                'sw-customer-base-form': true,
                'sw-card-view': true,
                'sw-button': await wrapTestComponent('sw-button'),
                'sw-button-deprecated': await wrapTestComponent('sw-button-deprecated'),
                'sw-button-process': await wrapTestComponent('sw-button-process'),
                'sw-icon': true,
                'sw-loader': true,
                'router-link': true,
            },
            provide: {
                numberRangeService: {},
                systemConfigApiService: {
                    getValues: () =>
                        Promise.resolve({
                            'core.register.minPasswordLength': 8,
                        }),
                },
                customerValidationService: {},
                repositoryFactory: {
                    create: (entity) => {
                        if (entity === 'customer') {
                            return {
                                create: () => {
                                    return {
                                        id: '63e27affb5804538b5b06cb4e344b130',
                                        addresses: new EntityCollection(
                                            '/customer_address',
                                            'customer_address',
                                            Context.api,
                                            null,
                                            [],
                                        ),
                                    };
                                },
                            };
                        }

                        if (entity === 'language') {
                            return {
                                searchIds: () =>
                                    Promise.resolve({
                                        total: 1,
                                        data: ['1'],
                                    }),
                            };
                        }

                        if (entity === 'salutation') {
                            return {
                                searchIds: () =>
                                    Promise.resolve({
                                        total: 1,
                                        data: ['salutationId'],
                                    }),
                            };
                        }

                        return {
                            create: () => Promise.resolve(),
                        };
                    },
                },
            },
        },
    });
}

describe('module/sw-customer/page/sw-customer-create', () => {
    it('should have valid email validation response when no email is given', async () => {
        const wrapper = await createWrapper();
        await wrapper.vm.$nextTick();

        await wrapper.setData({
            customer: {
                id: '1',
                email: null,
                boundSalesChannelId: null,
            },
        });

        const response = await wrapper.vm.validateEmail();

        expect(response.isValid).toBe(true);
    });

    it('should override context when the sales channel does not exist language compared to the API language', async () => {
        const wrapper = await createWrapper();
        wrapper.vm.validateEmail = jest.fn().mockImplementation(() => Promise.resolve({ isValid: true }));
        wrapper.vm.customerRepository.save = jest.fn((customer, context) => Promise.resolve(context));

        expect(await wrapper.vm.languageId).toEqual(Cicada.Context.api.languageId);

        await wrapper.setData({
            customer: {
                id: '1',
                email: 'user@domain.com',
                accountType: 'business',
                password: '12345678',
                salesChannelId: 'a7921464677a4ef591683d144beecd24',
            },
            address: {
                company: 'Cicada',
            },
        });

        expect(await wrapper.vm.languageId).toBe('1');

        const context = await wrapper.vm.onSave();

        expect(context.languageId).toBe('1');
    });

    it('should keep context when sales channel exists language compared to API language', async () => {
        const wrapper = await createWrapper();
        wrapper.vm.validateEmail = jest.fn().mockImplementation(() => Promise.resolve({ isValid: true }));
        wrapper.vm.customerRepository.save = jest.fn((customer, context) => Promise.resolve(context));

        wrapper.vm.languageRepository.searchIds = jest.fn(() =>
            Promise.resolve({
                total: 1,
                data: [Cicada.Context.api.languageId],
            }),
        );

        expect(await wrapper.vm.languageId).toEqual(Cicada.Context.api.languageId);

        await wrapper.setData({
            customer: {
                id: '1',
                email: 'user@domain.com',
                accountType: 'business',
                password: 'cicada',
                salesChannelId: 'a7921464677a4ef591683d144beecd24',
            },
            address: {
                company: 'Cicada',
            },
        });

        expect(await wrapper.vm.languageId).toEqual(Cicada.Context.api.languageId);

        const context = await wrapper.vm.onSave();

        expect(context.languageId).toEqual(Cicada.Context.api.languageId);
    });

    it('should get default salutation is value not specified', async () => {
        const wrapper = await createWrapper();
        await flushPromises();

        expect(wrapper.vm.customer.salutationId).toBe('salutationId');
    });

    it('should not render sw-customer-base-form and sw-customer-address-form if customer is null', async () => {
        const wrapper = await createWrapper();
        await flushPromises();
        await wrapper.setData({
            customer: null,
        });

        expect(wrapper.find('sw-customer-base-form-stub').exists()).toBeFalsy();
        expect(wrapper.find('sw-customer-address-form-stub').exists()).toBeFalsy();
    });

    it('should throw exception when the customer creation fails', async () => {
        const wrapper = await createWrapper();
        await wrapper.setData({
            customer: {
                id: '1',
                email: 'ytn@xchanming.com',
                boundSalesChannelId: null,
            },
        });

        wrapper.vm.customerRepository.save = jest.fn(() =>
            // eslint-disable-next-line prefer-promise-reject-errors
            Promise.reject({
                response: {
                    data: {
                        errors: [
                            {
                                code: 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                                detail: 'This value should not be blank.',
                                status: '400',
                                template: 'This value should not be blank.',
                            },
                        ],
                    },
                },
            }),
        );

        try {
            await wrapper.vm.onSave();
        } catch (e) {
            // eslint-disable-next-line jest/no-conditional-expect
            expect(e.response.data.errors[0]).toEqual({
                code: 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                detail: 'This value should not be blank.',
                status: '400',
                template: 'This value should not be blank.',
            });
        }
    });
});
