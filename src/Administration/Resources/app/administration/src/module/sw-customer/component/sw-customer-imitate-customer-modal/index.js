/**
 * @sw-package unknown
 */
import ApiService from 'src/core/service/api.service';
import template from './sw-customer-imitate-customer-modal.html.twig';
import './sw-customer-imitate-customer-modal.scss';

const { Mixin } = Cicada;
const { Criteria } = Cicada.Data;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'repositoryFactory',
        'contextStoreService',
    ],

    emits: ['modal-close'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        customer: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            salesChannelDomains: [],
        };
    },

    computed: {
        modalTitle() {
            return this.$tc('sw-customer.imitateCustomerModal.modalTitle', {
                name: this.customer.name,
            });
        },

        modalDescription() {
            return this.$tc('sw-customer.imitateCustomerModal.modalDescription', {
                name: this.customer.name,
            });
        },

        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        currentUser() {
            return Cicada.State.get('session').currentUser;
        },

        salesChannelDomainCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('salesChannel');
            criteria.addFilter(Criteria.equals('salesChannel.typeId', Cicada.Defaults.storefrontSalesChannelTypeId));
            criteria.addSorting(Criteria.sort('salesChannel.name', 'ASC'));
            criteria.addSorting(Criteria.sort('languageId', 'DESC'));

            if (this.customer.boundSalesChannelId) {
                criteria.addFilter(Criteria.equals('salesChannelId', this.customer.boundSalesChannelId));
            }

            return criteria;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.fetchSalesChannelDomains();
        },

        async onSalesChannelDomainMenuItemClick(salesChannelId, salesChannelDomainUrl) {
            this.contextStoreService
                .generateImitateCustomerToken(this.customer.id, salesChannelId)
                .then((response) => {
                    const handledResponse = ApiService.handleResponse(response);

                    this.contextStoreService.redirectToSalesChannelUrl(
                        salesChannelDomainUrl,
                        handledResponse.token,
                        this.customer.id,
                        this.currentUser?.id,
                    );
                })
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('sw-customer.detail.notificationImitateCustomerErrorMessage'),
                    });
                });
        },

        onCancel() {
            this.$emit('modal-close');
        },

        fetchSalesChannelDomains() {
            this.salesChannelDomainRepository
                .search(this.salesChannelDomainCriteria, Cicada.Context.api)
                .then((loadedDomains) => {
                    this.salesChannelDomains = loadedDomains;
                });
        },
    },
};
