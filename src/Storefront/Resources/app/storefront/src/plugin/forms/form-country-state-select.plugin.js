import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

/**
 * @package content
 */
export default class CountryStateSelectPlugin extends Plugin {

    static options = {
        countrySelectSelector: '.country-select',
        initialCountryAttribute: 'initial-country-id',
        countryStateSelectSelector: '.country-state-select',
        countryStateCitySelectSelector: '.country-state-city-select',
        countryStateCityDistrictSelectSelector: '.country-state-city-district-select',


        initialCountryStateAttribute: 'initial-country-state-id',
        initialCityAttribute: 'initial-city-id',
        initialDistrictAttribute: 'initial-district-id',
        countryStatePlaceholderSelector: '[data-placeholder-option="true"]',
        countryStateCityPlaceholderSelector: '[data-city-placeholder-option]',
        countryStateCityDistrictPlaceholderSelector: '[data-district-placeholder-option]',
        vatIdFieldInput: '#vatIds',
        zipcodeFieldInput: '[data-input-name="zipcodeInput"]',
        vatIdRequired: 'vat-id-required',
        stateRequired: 'state-required',
        zipcodeRequired: 'zipcode-required',
        zipcodeLabel: '#zipcodeLabel',
        scopeElementSelector: null,
        prefix: null,
    };

    init() {
        this.initClient();
        this.initSelects();

        this._getFormFieldToggleInstance();

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
            countrySelectSelector, countryStateSelectSelector,
            initialCountryAttribute, initialCountryStateAttribute
            , countryStateCitySelectSelector,countryStateCityDistrictSelectSelector,initialCityAttribute,initialDistrictAttribute,
        } = CountryStateSelectPlugin.options;
        const countrySelect = DomAccess.querySelector(this.scopeElement, countrySelectSelector);
        const countryStateSelect = DomAccess.querySelector(this.scopeElement, countryStateSelectSelector);
        const countryStateCitySelect = DomAccess.querySelector(this.scopeElement, countryStateCitySelectSelector);
        const countryStateCityDistrictSelect = DomAccess.querySelector(this.scopeElement, countryStateCityDistrictSelectSelector);

        const initialCountryId = DomAccess.getDataAttribute(countrySelect, initialCountryAttribute);
        const initialCountryStateId = DomAccess.getDataAttribute(countryStateSelect, initialCountryStateAttribute);
        const initialCityId = DomAccess.getDataAttribute(countryStateCitySelect, initialCityAttribute);
        const initialDistrictId = DomAccess.getDataAttribute(countryStateCityDistrictSelect, initialDistrictAttribute);


        const countrySelectCurrentOption = countrySelect.options[countrySelect.selectedIndex];
        const vatIdRequired = !!DomAccess.getDataAttribute(countrySelectCurrentOption, this.options.vatIdRequired, false);
        const vatIdInput = document.querySelector(this.options.vatIdFieldInput);
        const stateRequired = !!DomAccess.getDataAttribute(countrySelectCurrentOption, this.options.stateRequired, false);
        const zipcodeLabels = DomAccess.querySelectorAll(document, this.options.zipcodeLabel, false);
        const zipcodeInputs = DomAccess.querySelectorAll(document, this.options.zipcodeFieldInput, false);
        const zipcodeRequired = !!DomAccess.getDataAttribute(countrySelectCurrentOption, this.options.zipcodeRequired, false);

        countrySelect.addEventListener('change', this.onChangeCountry.bind(this));
        countryStateSelect.addEventListener('change', this.onChangeCountryState.bind(this));
        countryStateCitySelect.addEventListener('change', this.onChangeCountryStateCity.bind(this));

        if (!initialCountryId) {
            return;
        }

        this._countryId = initialCountryId;

        this.requestStateData(initialCountryId, initialCountryStateId, stateRequired);
        this.requestStateCityData(initialCountryId, initialCountryStateId, initialCityId);
        this.requestStateCityDistrictData(initialCountryId, initialCityId, initialDistrictId);

        if (zipcodeRequired) {
            this._updateZipcodeRequired(zipcodeLabels, zipcodeInputs, zipcodeRequired);
        }

        if (!vatIdInput) {
            return;
        }
        this._updateRequiredVatId(vatIdInput, vatIdRequired);
    }

    onChangeCountryState(event) {
        const stateId = event.target.value;
        this.requestStateCityData(this._countryId, stateId);
    }

    requestStateCityData(countryId, countryStateId = null,initCityId = null) {
        const payload = JSON.stringify({countryId, parentId: countryStateId});


        this._client.post(
            window.router['frontend.country.country-data'],
            payload,
            (response) => {
                let responseData = JSON.parse(response);
                responseData = {...responseData};
                updateStateCitySelect(responseData, initCityId, this.el, CountryStateSelectPlugin.options);
            }
        );
    }

    requestStateCityDistrictData(countryId, countryStateId = null,initialDistrictId = null) {
        const payload = JSON.stringify({countryId, parentId: countryStateId});

        this._client.post(
            window.router['frontend.country.country-data'],
            payload,
            (response) => {
                let responseData = JSON.parse(response);
                responseData = {...responseData};
                updateStateCityDistrictSelect(responseData, initialDistrictId, this.el, CountryStateSelectPlugin.options);
            }
        );
    }

    onChangeCountryStateCity(event) {
        const cityId = event.target.value;
        this.requestStateCityDistrictData(this._countryId, cityId);
    }

    onChangeCountry(event) {
        const countryId = event.target.value;
        this._countryId = countryId;
        const countrySelect = event.target.options[event.target.selectedIndex];
        const stateRequired = !!DomAccess.getDataAttribute(countrySelect, this.options.stateRequired);
        this.requestStateData(countryId, null, stateRequired);
        const vatIdRequired = DomAccess.getDataAttribute(countrySelect, this.options.vatIdRequired);
        const vatIdInput = document.querySelector(this.options.vatIdFieldInput);

        const zipcodeLabels = DomAccess.querySelectorAll(this.scopeElement, this.options.zipcodeLabel, false);
        const zipcodeInputs = DomAccess.querySelectorAll(this.scopeElement, this.options.zipcodeFieldInput, false);
        const zipcodeRequired = !!DomAccess.getDataAttribute(countrySelect, this.options.zipcodeRequired, false);

        this._updateZipcodeRequired(zipcodeLabels, zipcodeInputs, zipcodeRequired);

        if (vatIdInput) {
            this._updateRequiredVatId(vatIdInput, vatIdRequired);
        }
    }

    requestStateData(countryId, countryStateId = null, stateRequired = false) {
        const payload = JSON.stringify({countryId});
        this._client.post(
            window.router['frontend.country.country-data'],
            payload,
            (response) => {
                let responseData = JSON.parse(response);
                responseData = {...responseData, ...{stateRequired}};
                updateStateSelect(responseData, countryStateId, this.el, CountryStateSelectPlugin.options);
            }
        );
    }

    _updateRequiredVatId(vatIdFieldInput, vatIdRequired) {
        if (this._differentShippingCheckbox && this.options.prefix === 'billingAddress') {
            return;
        }

        const label = vatIdFieldInput.parentNode.querySelector('label');

        if (vatIdRequired) {
            vatIdFieldInput.setAttribute('required', 'required');

            if (label?.textContent && label.textContent.substr(-1, 1) !== '*') {
                label.textContent = `${label.textContent}*`;
            }

            return;
        }

        if (label?.textContent && label.textContent.substr(-1, 1) === '*') {
            label.textContent = label.textContent.substr(0, label.textContent.length - 1);
        }

        vatIdFieldInput.removeAttribute('required');
    }

    _updateZipcodeRequired(labels, inputs, required) {
        if (!labels || !inputs) {
            return;
        }

        labels.forEach(label => {
            label.className = required ? '' : 'd-none';
        });

        inputs.forEach(input => {
            if (required) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
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

        this._updateRequiredVatId(vatIdInput, vatIdRequired);
    }
}

function updateStateCitySelect({states}, countryStateId, rootElement, options) {

    const {
        countryStateCitySelectSelector,
        countryStateCityPlaceholderSelector,
        countryStateCityDistrictSelectSelector,
        countryStateCityDistrictPlaceholderSelector,
    } = options;
    const countryStateCitySelect = DomAccess.querySelector(rootElement, countryStateCitySelectSelector);
    const countryStateCityDistrictSelect = DomAccess.querySelector(rootElement, countryStateCityDistrictSelectSelector);
    removeOldOptions(countryStateCityDistrictSelect, `option:not(${countryStateCityDistrictPlaceholderSelector})`);
    removeOldOptions(countryStateCitySelect, `option:not(${countryStateCityPlaceholderSelector})`);
    addNewStates(countryStateCitySelect, states, countryStateId);
    updateRequiredState(countryStateCitySelect, `option${countryStateCityPlaceholderSelector}`);
}

function updateStateCityDistrictSelect({states}, countryStateId, rootElement, options) {

    const {countryStateCityDistrictSelectSelector, countryStateCityDistrictPlaceholderSelector} = options;
    const countryStateCityDistrictSelect = DomAccess.querySelector(rootElement, countryStateCityDistrictSelectSelector);

    removeOldOptions(countryStateCityDistrictSelect, `option:not(${countryStateCityDistrictPlaceholderSelector})`);
    addNewStates(countryStateCityDistrictSelect, states, countryStateId);
    updateRequiredState(countryStateCityDistrictSelect, `option${countryStateCityDistrictPlaceholderSelector}`);
}



function updateStateSelect({stateRequired, states}, countryStateId, rootElement, options) {
    const {countryStateSelectSelector, countryStatePlaceholderSelector} = options;
    const countryStateSelect = DomAccess.querySelector(rootElement, countryStateSelectSelector);

    removeOldOptions(countryStateSelect, `option:not(${countryStatePlaceholderSelector})`);
    addNewStates(countryStateSelect, states, countryStateId);
    updateRequiredState(countryStateSelect, stateRequired, `option${countryStatePlaceholderSelector}`);
}

function removeOldOptions(el, optionQuery) {
    el.querySelectorAll(optionQuery).forEach((option) => option.remove());
}

function addNewStates(selectEl, states, selectedStateId) {
    if (selectEl === null) {
        return;
    }
    if (states.length === 0) {
        selectEl.parentNode.classList.add('d-none');
        selectEl.setAttribute('disabled', 'disabled');
        return;
    }
    states.map(option => createOptionFromState(option, selectedStateId))
        .forEach((option) => {
            selectEl.append(option);
        });
    selectEl.parentNode.classList.remove('d-none');
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

function updateRequiredState(countryStateSelect, stateRequired, placeholderQuery) {
    const placeholder = countryStateSelect.querySelector(placeholderQuery);
    if (!placeholder){
        return;
    }
    const label = countryStateSelect.parentNode.querySelector('label');

    if (stateRequired) {
        placeholder.setAttribute('disabled', 'disabled');
        countryStateSelect.setAttribute('required', 'required');

        if (label?.textContent && label.textContent.substr(-1, 1) !== '*') {
            label.textContent = `${label.textContent.trim()}*`;
        }

        return;
    }

    if (label?.textContent && label.textContent.substr(-1, 1) === '*') {
        label.textContent = label.textContent.substr(0, label.textContent.length - 1);
    }

    placeholder.removeAttribute('disabled');
    countryStateSelect.removeAttribute('required');
}
