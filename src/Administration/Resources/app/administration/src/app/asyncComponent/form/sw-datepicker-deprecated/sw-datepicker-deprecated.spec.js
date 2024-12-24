/**
 * @package admin
 */

import { flushPromises, mount } from '@vue/test-utils';

async function createWrapper(customOptions = {}) {
    const wrapper = mount(await wrapTestComponent('sw-datepicker-deprecated', { sync: true }), {
        global: {
            stubs: {
                'sw-base-field': await wrapTestComponent('sw-base-field'),
                'sw-contextual-field': await wrapTestComponent('sw-contextual-field'),
                'sw-block-field': await wrapTestComponent('sw-block-field'),
                'sw-icon': true,
                'sw-field-error': true,
                'sw-inheritance-switch': true,
                'sw-ai-copilot-badge': true,
                'sw-help-text': true,
            },
        },
        ...customOptions,
    });
    await flushPromises();
    return wrapper;
}

describe('src/app/component/form/sw-datepicker', () => {
    let wrapper;
    const currentUser = Cicada.State.get('session').currentUser;

    beforeEach(async () => {
        Cicada.State.commit('setCurrentUser', { timeZone: 'UTC' });
    });

    afterAll(() => {
        Cicada.State.commit('setCurrentUser', currentUser);
    });

    it('should have enabled links', async () => {
        wrapper = await createWrapper();

        const contextualField = wrapper.find('.sw-contextual-field');
        const flatpickrInput = wrapper.find('.flatpickr-input');

        expect(contextualField.attributes().disabled).toBeUndefined();
        expect(flatpickrInput.attributes().disabled).toBeUndefined();
    });

    it('should show the dateformat, when no placeholderText is provided', async () => {
        wrapper = await createWrapper();

        const flatpickrInput = wrapper.find('.flatpickr-input');

        expect(flatpickrInput.attributes().placeholder).toBe('d/m/Y');
    });

    it('should show the placeholderText, when provided', async () => {
        const placeholder = 'Stop! Hammertime!';
        wrapper = await createWrapper({
            props: {
                placeholder,
            },
        });

        const flatpickrInput = wrapper.find('.flatpickr-input');

        expect(flatpickrInput.attributes().placeholder).toBe(placeholder);
    });

    it('should use the admin locale', async () => {
        Cicada.State.get('session').currentLocale = 'de-DE';
        wrapper = await createWrapper();

        expect(wrapper.vm.$data.flatpickrInstance.config.locale).toBe('de');

        Cicada.State.get('session').currentLocale = 'en-GB';
        await flushPromises();

        expect(wrapper.vm.$data.flatpickrInstance.config.locale).toBe('en');
    });

    it('should show the label from the property', async () => {
        wrapper = await createWrapper({
            props: {
                label: 'Label from prop',
            },
        });

        expect(wrapper.find('label').text()).toBe('Label from prop');
    });

    it('should show the value from the label slot', async () => {
        wrapper = mount(
            {
                template: `
               <sw-datepicker label="Label from prop">
                 <template #label>
                      Label from slot
                 </template>
             </sw-datepicker>`,
            },
            {
                global: {
                    stubs: {
                        'sw-datepicker': await wrapTestComponent('sw-datepicker-deprecated', { sync: true }),
                        'sw-base-field': await wrapTestComponent('sw-base-field', { sync: true }),
                        'sw-contextual-field': await wrapTestComponent('sw-contextual-field', { sync: true }),
                        'sw-block-field': await wrapTestComponent('sw-block-field', { sync: true }),
                        'sw-icon': true,
                        'sw-field-error': true,
                        'sw-inheritance-switch': true,
                        'sw-ai-copilot-badge': true,
                        'sw-help-text': true,
                    },
                },
            },
        );
        await flushPromises();

        expect(wrapper.find('label').text()).toBe('Label from slot');
    });

    it.each([
        { dateType: 'date', timeZone: 'UTC', expectedTimeZone: 'UTC' },
        {
            dateType: 'date',
            timeZone: 'Europe/Berlin',
            expectedTimeZone: 'UTC',
        },
        { dateType: 'time', timeZone: 'UTC', expectedTimeZone: 'UTC' },
        {
            dateType: 'time',
            timeZone: 'Europe/Berlin',
            expectedTimeZone: 'UTC',
        },
        { dateType: 'datetime', timeZone: 'UTC', expectedTimeZone: 'UTC' },
        {
            dateType: 'datetime',
            timeZone: 'Europe/Berlin',
            expectedTimeZone: 'Europe/Berlin',
        },
    ])(
        'should show the $expectedTimeZone timezone as a hint when the $timeZone timezone was selected and dateType is $dateType and hideHint is false',
        async ({ dateType, timeZone, expectedTimeZone }) => {
            Cicada.State.commit('setCurrentUser', { timeZone: timeZone });

            wrapper = await createWrapper({
                props: {
                    dateType,
                    hideHint: false,
                },
            });

            const hint = wrapper.find('.sw-field__hint');
            const clockIcon = hint.find('sw-icon-stub[name="solid-clock"]');

            expect(hint.text()).toContain(expectedTimeZone);
            expect(clockIcon.isVisible()).toBe(true);
        },
    );

    it.each([
        { dateType: 'date', timeZone: 'UTC' },
        { dateType: 'date', timeZone: 'Europe/Berlin' },
        { dateType: 'time', timeZone: 'UTC' },
        { dateType: 'time', timeZone: 'Europe/Berlin' },
        { dateType: 'datetime', timeZone: 'UTC' },
        { dateType: 'datetime', timeZone: 'Europe/Berlin' },
    ])(
        'should show no timezone as a hint when the $timeZone timezone was selected and dateType is $dateType and hideHint is true',
        async ({ dateType, timeZone }) => {
            Cicada.State.commit('setCurrentUser', { timeZone: timeZone });

            wrapper = await createWrapper({
                props: {
                    dateType,
                    hideHint: true,
                },
            });

            expect(wrapper.find('.sw-field__hint').exists()).toBe(false);
        },
    );

    it('should not convert the date when a timezone is set and dateType is date', async () => {
        Cicada.State.commit('setCurrentUser', { timeZone: 'Europe/Berlin' });

        wrapper = await createWrapper({
            props: {
                value: '2023-03-27T00:00:00.000+00:00',
                dateType: 'date',
            },
        });

        // Can't test with DOM because of the flatpickr dependency
        expect(wrapper.vm.timezoneFormattedValue).toBe('2023-03-27T00:00:00.000+00:00');
    });

    it('should not emit a converted date when a timezone is set and dateType is date', async () => {
        Cicada.State.commit('setCurrentUser', { timeZone: 'Europe/Berlin' });

        wrapper = await createWrapper({
            props: {
                value: '2023-03-27T00:00:00.000+00:00',
                dateType: 'date',
            },
        });

        // can't test with DOM because of the flatpickr dependency
        wrapper.vm.timezoneFormattedValue = '2023-03-22T00:00:00.000+00:00';

        expect(wrapper.emitted('update:value')[0]).toEqual([
            '2023-03-22T00:00:00.000+00:00',
        ]);
    });

    it('should not convert the date when a timezone is set and dateType is time', async () => {
        Cicada.State.commit('setCurrentUser', { timeZone: 'Europe/Berlin' });

        wrapper = await createWrapper({
            props: {
                value: '2023-03-27T00:00:00.000+00:00',
                dateType: 'time',
            },
        });

        // Can't test with DOM because of the flatpickr dependency
        expect(wrapper.vm.timezoneFormattedValue).toBe('2023-03-27T00:00:00.000+00:00');
    });

    it('should not emit a converted date when a timezone is set and dateType is time', async () => {
        Cicada.State.commit('setCurrentUser', { timeZone: 'Europe/Berlin' });

        wrapper = await createWrapper({
            props: {
                value: '2023-03-27T00:00:00.000+00:00',
                dateType: 'time',
            },
        });

        // can't test with DOM because of the flatpickr dependency
        wrapper.vm.timezoneFormattedValue = '2023-03-22T00:00:00.000+00:00';

        expect(wrapper.emitted('update:value')[0]).toEqual([
            '2023-03-22T00:00:00.000+00:00',
        ]);
    });

    it('should convert the date when a timezone is set and dateType is dateTime', async () => {
        Cicada.State.commit('setCurrentUser', { timeZone: 'Europe/Berlin' });

        wrapper = await createWrapper({
            props: {
                value: '2023-03-27T00:00:00.000+00:00',
                dateType: 'datetime',
            },
        });

        // Can't test with DOM because of the flatpickr dependency
        expect(wrapper.vm.timezoneFormattedValue).toBe('2023-03-27T02:00:00.000Z');
    });

    it('should emit a converted date when a timezone is set and dateType is dateTime', async () => {
        Cicada.State.commit('setCurrentUser', { timeZone: 'Europe/Berlin' });

        wrapper = await createWrapper({
            props: {
                value: '2023-03-27T00:00:00.000+00:00',
                dateType: 'datetime',
            },
        });

        // can't test with DOM because of the flatpickr dependency
        wrapper.vm.timezoneFormattedValue = '2023-03-22T00:00:00.000+00:00';

        expect(wrapper.emitted('update:value')[0]).toEqual([
            '2023-03-21T23:00:00.000Z',
        ]);
    });

    it('should emit a date when is typed', async () => {
        wrapper = await createWrapper({});

        const input = wrapper.find('.form-control.input');

        await input.trigger('focus');
        input.element.value = '2023-03-27';
        await input.trigger('input');
        await input.trigger('blur');

        expect(wrapper.emitted('update:value')).toHaveLength(1);
    });

    it('should support other locales formats', async () => {
        Cicada.State.get('session').currentLocale = 'en-US';
        wrapper = await createWrapper({});
        let input = wrapper.find('.form-control.input');
        input.element.value = '12/25/2024';
        await input.trigger('input');
        await input.trigger('keydown.enter');

        expect(input.element.value).toBe('12/25/2024');

        Cicada.State.get('session').currentLocale = 'en-UK';

        wrapper = await createWrapper({});
        input = wrapper.find('.form-control.input');
        input.element.value = '25/12/2024';
        await input.trigger('input');
        await input.trigger('keydown.enter');

        expect(input.element.value).toBe('25/12/2024');
    });
});
