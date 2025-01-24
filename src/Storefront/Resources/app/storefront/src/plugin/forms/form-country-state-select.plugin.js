import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

/**
 * @package content
 */
export default class CountryStateSelectPlugin extends Plugin {

    static options = {
        countrySelectSelector: '.country-select',
        countryStateSelectSelector: '.country-state-select',
        countryStateCitySelectSelector: '.country-state-city-select',
        countryStateCityDistrictSelectSelector: '.country-state-city-district-select',

        initialCountryAttribute: 'initial-country-id',
        initialCountryStateAttribute: 'initial-country-state-id',
        initialCityAttribute: 'initial-city-id',
        initialDistrictAttribute: 'initial-district-id',
        countryStatePlaceholderSelector: '[data-placeholder-option="true"]',
        countryStateCityPlaceholderSelector: '[data-city-placeholder-option]',
        countryStateCityDistrictPlaceholderSelector: '[data-district-placeholder-option]',
        vatIdFieldInput: '#vatIds',
        zipcodeFieldInput: '[data-input-name="zipcodeInput"]',
        vatIdRequired: 'vat-id-required',
        zipcodeRequired: 'zipcode-required',
        zipcodeLabel: '#zipcodeLabel',
        scopeElementSelector: null,
        prefix: null,
    };

    init() {
        this.initClient();
        this.initSelects();

        this._getFormFieldToggleInstance();
        this._country = {};
        if (this._formFieldToggleInstance) {
            this._formFieldToggleInstance.$emitter.subscribe('onChange', this._onFormFieldToggleChange.bind(this));
        }
    }

    initClient() {
        this._client = new HttpClient();
    }

    initSelects() {
        this.scopeElement = this.el;

        if (this.options.scopeElementSelector) {
            this.scopeElement = DomAccess.querySelector(document, this.options.scopeElementSelector);
        }

        const {
            countrySelectSelector,
            countryStateSelectSelector,
            countryStateCitySelectSelector,
            countryStateCityDistrictSelectSelector,
            initialCountryAttribute,
            initialCountryStateAttribute,
            initialCityAttribute,
            initialDistrictAttribute,
        } = CountryStateSelectPlugin.options;

        const selectsConfig = [
            {
                selector: countrySelectSelector,
                initialAttribute: initialCountryAttribute,
                changeHandler: this.onChangeCountry.bind(this),
            },
            {
                selector: countryStateSelectSelector,
                initialAttribute: initialCountryStateAttribute,
                changeHandler: this.onChangeCountryState.bind(this),
            },
            {
                selector: countryStateCitySelectSelector,
                initialAttribute: initialCityAttribute,
                changeHandler: this.onChangeCountryStateCity.bind(this),
            },
            {
                selector: countryStateCityDistrictSelectSelector,
                initialAttribute: initialDistrictAttribute,
            },
        ];

        this.selectElements = {};

        selectsConfig.forEach(({selector, initialAttribute, changeHandler}) => {
            const selectElement = DomAccess.querySelector(this.scopeElement, selector);
            this.selectElements[selector] = {
                element: selectElement,
                initialValue: DomAccess.getDataAttribute(selectElement, initialAttribute, false),
            };

            if (changeHandler) {
                selectElement.addEventListener('change', changeHandler);
            }
        });

        const {initialValue: initialCountryId} = this.selectElements[countrySelectSelector];
        const {initialValue: initialCountryStateId} = this.selectElements[countryStateSelectSelector];
        const {initialValue: initialCityId} = this.selectElements[countryStateCitySelectSelector];
        const {initialValue: initialDistrictId} = this.selectElements[countryStateCityDistrictSelectSelector];

        if (!initialCountryId) {
            return;
        }

        this._countryId = initialCountryId;
        this._stateId = initialCountryStateId;
        this._cityId = initialCityId;
        this._districtId = initialDistrictId;

        const countrySelect = DomAccess.querySelector(this.scopeElement, countrySelectSelector);

        const countrySelectCurrentOption = countrySelect.options[countrySelect.selectedIndex];

        this.requestStateData(initialCountryId, initialCountryStateId, initialCityId, initialDistrictId);

        const vatIdRequired = !!DomAccess.getDataAttribute(countrySelectCurrentOption, this.options.vatIdRequired, false);
        const vatIdInput = document.querySelector(this.options.vatIdFieldInput);

        const zipcodeInputs = DomAccess.querySelectorAll(this.scopeElement, this.options.zipcodeFieldInput, false);
        const zipcodeRequired = !!DomAccess.getDataAttribute(countrySelectCurrentOption, this.options.zipcodeRequired, false);

        if (zipcodeRequired) {
            this._updateZipcodeFields(zipcodeInputs, zipcodeRequired);
        }

        if (!vatIdInput) {
            return;
        }
        this._updateVatIdField(vatIdInput, vatIdRequired);
    }

    onChangeCountryState(event) {
        this._stateId = event.target.value;

        const {
            countryStateCitySelectSelector,
            countryStateCityPlaceholderSelector,
        } = CountryStateSelectPlugin.options;

        _updateStateSelect(
            Object.values(this._country[this._countryId][this._stateId].children),
            null,
            this.el,
            countryStateCitySelectSelector,
            countryStateCityPlaceholderSelector
        );
        this.onChangeCountryStateCity({target: {value: null}});
    }

    onChangeCountryStateCity(event) {
        const cityId = event.target.value;

        const {
            countryStateCityDistrictSelectSelector,
            countryStateCityDistrictPlaceholderSelector,
        } = CountryStateSelectPlugin.options;
        const children = cityId
            ? Object.values(this._country[this._countryId][this._stateId].children[cityId]?.children || [])
            : [];
        _updateStateSelect(
            children,
            null,
            this.el,
            countryStateCityDistrictSelectSelector,
            countryStateCityDistrictPlaceholderSelector
        );
    }

    onChangeCountry(event) {
        const countryId = event.target.value;
        this._countryId = countryId;
        const countrySelect = event.target.options[event.target.selectedIndex];
        this.requestStateData(countryId, null);

        const vatIdRequired = DomAccess.getDataAttribute(countrySelect, this.options.vatIdRequired);
        const vatIdInput = document.querySelector(this.options.vatIdFieldInput);

        const zipcodeInputs = DomAccess.querySelectorAll(this.scopeElement, this.options.zipcodeFieldInput, false);
        const zipcodeRequired = !!DomAccess.getDataAttribute(countrySelect, this.options.zipcodeRequired, false);

        this._updateZipcodeFields(zipcodeInputs, zipcodeRequired);

        if (vatIdInput) {
            this._updateVatIdField(vatIdInput, vatIdRequired);
        }
    }

    requestStateData(countryId, countryStateId, cityId, districtId) {
        const payload = JSON.stringify({countryId});

        this._client.post(
            window.router['frontend.country.country-data'],
            payload,
            (response) => {
                const responseData = JSON.parse(response);
                this._country[countryId] = preprocessStateData(responseData.states);

                const {
                    countryStateSelectSelector, countryStatePlaceholderSelector,
                    countryStateCitySelectSelector,
                    countryStateCityDistrictSelectSelector,
                } = CountryStateSelectPlugin.options;

                _updateStateSelect(
                    Object.values(this._country[countryId]),
                    countryStateId,
                    this.el,
                    countryStateSelectSelector,
                    countryStatePlaceholderSelector
                );

                if (countryStateId) {
                    this.selectElements[countryStateSelectSelector].element.value = countryStateId;
                    this.onChangeCountryState({target: {value: countryStateId}});
                }

                if (cityId) {
                    this.selectElements[countryStateCitySelectSelector].element.value = cityId;
                    this.onChangeCountryStateCity({target: {value: cityId}});
                }

                if (districtId) {
                    this.selectElements[countryStateCityDistrictSelectSelector].element.value = districtId;
                }
            }
        );
    }


    /**
     * Updates the required state of the VAT id field.
     *
     * @param {HTMLElement} vatIdFieldInput
     * @param {boolean} vatIdRequired
     * @private
     */
    _updateVatIdField(vatIdFieldInput, vatIdRequired) {
        if (this._differentShippingCheckbox && this.options.prefix === 'billingAddress') {
            return;
        }

        if (vatIdRequired) {
            window.formValidation.setFieldRequired(vatIdFieldInput);
        } else {
            window.formValidation.setFieldNotRequired(vatIdFieldInput);
        }
    }

    /**
     * Updates the required state of the zip code fields.
     *
     * @param {NodeList} inputs
     * @param {boolean} required
     * @private
     */
    _updateZipcodeFields(inputs, required = false) {
        if (!inputs) {
            return;
        }

        inputs.forEach((input) => {
            if (required === true) {
                window.formValidation.setFieldRequired(input);
            } else {
                window.formValidation.setFieldNotRequired(input);
            }
        });
    }

    _getFormFieldToggleInstance() {
        const toggleField = DomAccess.querySelector(document, '[data-form-field-toggle-target=".js-form-field-toggle-shipping-address"]', false);
        if (!toggleField) {
            return;
        }

        this._formFieldToggleInstance = window.PluginManager.getPluginInstanceFromElement(toggleField, 'FormFieldToggle');
    }

    _onFormFieldToggleChange(event) {
        this._differentShippingCheckbox = event.target.checked;

        const scopeElementSelector = this._differentShippingCheckbox ? '.register-shipping' : '.register-billing';
        const scopeElement = DomAccess.querySelector(document, scopeElementSelector);

        const countrySelect = DomAccess.querySelector(scopeElement, this.options.countrySelectSelector);
        const countrySelectCurrentOption = countrySelect.options[countrySelect.selectedIndex];

        const vatIdRequired = !!DomAccess.getDataAttribute(countrySelectCurrentOption, this.options.vatIdRequired, false);
        const vatIdInput = document.querySelector(this.options.vatIdFieldInput);

        if (!vatIdInput) {
            return;
        }

        this._updateVatIdField(vatIdInput, vatIdRequired);
    }
}

function _updateStateSelect(states, countryStateId,
                            rootElement, selector, placeholderSelector) {

    const countryStateSelect = DomAccess.querySelector(rootElement, selector);

    removeOldOptions(countryStateSelect, `option:not(${placeholderSelector})`);
    addNewStates(countryStateSelect, states, countryStateId);
    updateRequiredState(countryStateSelect, `option${placeholderSelector}`);
}

function removeOldOptions(el, optionQuery) {
    el.querySelectorAll(optionQuery).forEach((option) => option.remove());
}

function addNewStates(selectEl, states, selectedStateId) {
    if (selectEl === null) {
        return;
    }
    if (states.length === 0) {
        selectEl.setAttribute('disabled', 'disabled');
        return;
    }
    states.map(option => createOptionFromState(option, selectedStateId))
        .forEach((option) => {
            selectEl.append(option);
        });
    selectEl.removeAttribute('disabled');
}

function createOptionFromState(state, selectedStateId) {
    const option = document.createElement('option');

    option.setAttribute('value', state.id);
    option.innerText = state.translated.name;

    if (state.id === selectedStateId) {
        option.setAttribute('selected', 'selected');
    }

    return option;
}

function updateRequiredState(countryStateSelect, placeholderQuery) {
    const placeholder = countryStateSelect.querySelector(placeholderQuery);
    if (!placeholder) {
        return;
    }
    const label = countryStateSelect.parentNode.querySelector('label');

    if (label?.textContent && label.textContent.substr(-1, 1) === '*') {
        label.textContent = label.textContent.substr(0, label.textContent.length - 1);
    }

    placeholder.removeAttribute('disabled');
    countryStateSelect.removeAttribute('required');
}


function preprocessStateData(data) {
    return data.reduce((index, province) => {
        index[province.id] = {id: province.id, name: province.name, translated: province.translated, children: {}};

        province.children?.forEach(city => {
            const cityData = {id: city.id, name: city.name, translated: city.translated, children: {}};
            index[province.id].children[city.id] = cityData;
            city.children?.forEach(area => {
                cityData.children[area.id] = {id: area.id, name: area.name, translated: area.translated};
            });
        });

        return index;
    }, {});
}
