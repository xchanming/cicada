import { mapPropertyErrors } from 'src/app/service/map-errors.service';
import template from './sw-settings-shipping-detail.html.twig';
import './sw-settings-shipping-detail.scss';
import swShippingDetailState from './state';

const { Mixin, Context } = Cicada;
const { mapState, mapGetters } = Cicada.Component.getComponentHelper();
const { Criteria } = Cicada.Data;
const { warn } = Cicada.Utils.debug;

/**
 * @sw-package checkout
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'ruleConditionDataProviderService',
        'repositoryFactory',
        'acl',
        'customFieldDataProviderService',
        'feature',
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
    ],

    shortcuts: {
        'SYSTEMKEY+S': 'onSave',
        ESCAPE: 'onCancel',
    },

    props: {
        shippingMethodId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            logoMediaItem: null,
            uploadTag: 'sw-shipping-method-upload-tag',
            isSaveSuccessful: false,
            isProcessLoading: false,
            isLoading: false,
            currenciesLoading: false,
            customFieldSets: [],
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier),
        };
    },

    computed: {
        ...mapState('swShippingDetail', [
            'shippingMethod',
            'currencies',
            'restrictedRuleIds',
        ]),

        ...mapGetters('swShippingDetail', [
            'usedRules',
        ]),

        ...mapPropertyErrors('shippingMethod', [
            'name',
            'technicalName',
            'deliveryTimeId',
            'availabilityRuleId',
        ]),

        identifier() {
            return this.placeholder(this.shippingMethod, 'name');
        },

        shippingMethodRepository() {
            return this.repositoryFactory.create('shipping_method');
        },

        shippingMethodPricesRepository() {
            return this.repositoryFactory.create('shipping_method_price');
        },

        currencyRepository() {
            return this.repositoryFactory.create('currency');
        },

        isNewShippingMethod() {
            return Object.keys(this.shippingMethod).length > 0 && this.shippingMethod.isNew();
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        deliveryTimeRepository() {
            return this.repositoryFactory.create('delivery_time');
        },

        deliveryTimeCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort('unit'));
            criteria.addSorting(Criteria.sort('name'));
            return criteria;
        },

        tooltipSave() {
            const systemKey = this.$device.getSystemKey();

            return {
                message: `${systemKey} + S`,
                appearance: 'light',
            };
        },

        tooltipCancel() {
            return {
                message: 'ESC',
                appearance: 'light',
            };
        },

        ruleFilter() {
            const criteria = new Criteria(1, 25);
            criteria.addFilter(
                Criteria.multi('OR', [
                    Criteria.contains('rule.moduleTypes.types', 'shipping'),
                    Criteria.equals('rule.moduleTypes', null),
                ]),
            );

            criteria.addAssociation('conditions');

            return criteria;
        },

        shippingMethodCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addAssociation('tags');

            criteria.getAssociation('prices').addAssociation('rule').addSorting(Criteria.sort('quantityStart'));

            if (!Cicada.Feature.isActive('v6.7.0.0')) {
                criteria.getAssociation('prices').addAssociation('calculationRule');
            }

            return criteria;
        },

        showCustomFields() {
            return this.customFieldSets && this.customFieldSets.length > 0;
        },
    },

    watch: {
        'shippingMethod.mediaId'() {
            if (this.shippingMethod.mediaId) {
                this.setMediaItem({ targetId: this.shippingMethod.mediaId });
            }
        },

        shippingMethodId() {
            // We must reset the page if the user clicks his browsers back button and navigates back to create
            this.createdComponent();
        },
    },

    beforeCreate() {
        Cicada.State.registerModule('swShippingDetail', swShippingDetailState);
    },

    created() {
        this.createdComponent();
    },

    beforeUnmount() {
        Cicada.State.unregisterModule('swShippingDetail');
    },

    methods: {
        createdComponent() {
            if (!this.shippingMethodId) {
                Cicada.State.commit('context/resetLanguageToDefault');

                const shippingMethod = this.shippingMethodRepository.create();
                const shippingMethodPrice = this.shippingMethodPricesRepository.create();
                shippingMethodPrice.calculation = 1;
                shippingMethodPrice.quantityStart = 1;
                shippingMethodPrice.shippingMethodId = shippingMethod.id;
                shippingMethodPrice.ruleId = null;
                shippingMethod.prices.add(shippingMethodPrice);
                Cicada.State.commit('swShippingDetail/setShippingMethod', shippingMethod);
            } else {
                this.loadEntityData();
            }
            this.loadCurrencies();
        },

        onSaveRule(ruleId) {
            this.shippingMethod.availabilityRuleId = ruleId;
        },

        loadCurrencies() {
            this.currenciesLoading = true;
            this.currencyRepository.search(new Criteria(1, 500), Context.api).then((currencyResponse) => {
                Cicada.State.commit('swShippingDetail/setCurrencies', this.sortCurrencies(currencyResponse));
                this.currenciesLoading = false;
            });
        },

        loadEntityData() {
            if (!this.shippingMethodId) {
                return;
            }

            this.isLoading = true;

            this.shippingMethodRepository
                .get(this.shippingMethodId, Cicada.Context.api, this.shippingMethodCriteria)
                .then((res) => {
                    Cicada.State.commit('swShippingDetail/setShippingMethod', res);

                    this.ruleConditionDataProviderService.getRestrictedRules('shippingMethodPrices').then((result) => {
                        Cicada.State.commit('swShippingDetail/setRestrictedRuleIds', this.usedRules.concat(result));
                    });

                    this.loadCustomFieldSets().then(() => {
                        this.isLoading = false;
                    });
                });
        },

        loadCustomFieldSets() {
            return this.customFieldDataProviderService.getCustomFieldSets('shipping_method').then((sets) => {
                this.customFieldSets = sets;
            });
        },

        abortOnLanguageChange() {
            return this.shippingMethodRepository.hasChanges(this.shippingMethod);
        },

        saveOnLanguageChange() {
            return this.onSave();
        },

        onChangeLanguage() {
            this.loadEntityData();
        },

        onSave() {
            this.filterIncompletePrices();

            this.isSaveSuccessful = false;
            this.isProcessLoading = true;

            return this.shippingMethodRepository
                .save(this.shippingMethod, Context.api)
                .then(() => {
                    this.isSaveSuccessful = true;
                    if (!this.shippingMethodId) {
                        this.$router.push({
                            name: 'sw.settings.shipping.detail',
                            params: { id: this.shippingMethod.id },
                        });
                    }
                    this.$refs.mediaSidebarItem.getList();
                    this.loadEntityData();
                })
                .catch((exception) => {
                    this.onError(exception);
                    warn(this._name, exception.message, exception.response);
                    this.isProcessLoading = false;
                    throw exception;
                })
                .finally(() => {
                    this.isProcessLoading = false;
                });
        },

        onError(error) {
            let errorDetails = null;

            try {
                errorDetails = error.response.data.errors[0].detail;
            } catch (e) {
                errorDetails = '';
            }

            this.createNotificationError({
                title: this.$tc('global.default.error'),
                // eslint-disable-next-line max-len
                message: `${this.$tc('sw-settings-shipping.detail.messageSaveError', 0, { name: this.shippingMethod.name })} ${errorDetails}`,
            });
        },

        filterIncompletePrices() {
            this.getIncompletePrices().forEach((incompletePrice) => {
                this.shippingMethod.prices.remove(incompletePrice.id);
            });
        },

        getIncompletePrices() {
            return this.shippingMethod.prices.filter((price) => {
                return (!price.calculation && !price.calculationRuleId) || price._inNewMatrix;
            });
        },

        onCancel() {
            this.$router.push({ name: 'sw.settings.shipping.index' });
        },

        setMediaItem({ targetId }) {
            this.mediaRepository.get(targetId, Context.api).then((updatedMedia) => {
                this.logoMediaItem = updatedMedia;
            });
            this.shippingMethod.mediaId = targetId;
        },

        onDropMedia(mediaItem) {
            this.setMediaItem({ targetId: mediaItem.id });
        },

        setMediaFromSidebar(mediaEntity) {
            this.logoMediaItem = mediaEntity;
            this.shippingMethod.mediaId = mediaEntity.id;
        },

        onUnlinkLogo() {
            this.logoMediaItem = null;
            this.shippingMethod.mediaId = null;
        },

        openMediaSidebar() {
            this.$refs.mediaSidebarItem.openContent();
        },

        sortCurrencies(currencies) {
            currencies.sort((a, b) => {
                if (a.isSystemDefault) {
                    return -1;
                }
                if (b.isSystemDefault) {
                    return 1;
                }
                if (a.translated.name < b.translated.name) {
                    return -1;
                }
                if (a.translated.name > b.translated.name) {
                    return 1;
                }
                return 0;
            });

            return currencies;
        },
    },
};
