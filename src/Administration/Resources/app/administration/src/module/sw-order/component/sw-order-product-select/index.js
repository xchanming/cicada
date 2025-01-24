import template from './sw-order-product-select.html.twig';
import { LineItemType, PriceType } from '../../order.types';
import './sw-order-product-select.scss';

/**
 * @sw-package checkout
 */

const { Service } = Cicada;
const { Criteria } = Cicada.Data;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        item: {
            type: Object,
            required: true,
        },

        salesChannelId: {
            type: String,
            required: true,
            default: '',
        },

        taxStatus: {
            type: String,
            required: true,
            default: '',
        },
    },

    data() {
        return {
            product: null,
        };
    },

    computed: {
        productRepository() {
            return Service('repositoryFactory').create('product');
        },

        lineItemTypes() {
            return LineItemType;
        },

        lineItemPriceTypes() {
            return PriceType;
        },

        isShownProductSelect() {
            return this.item._isNew && this.item.type === this.lineItemTypes.PRODUCT;
        },

        isShownItemLabelInput() {
            return this.item.type !== this.lineItemTypes.PRODUCT;
        },

        contextWithInheritance() {
            return { ...Cicada.Context.api, inheritance: true };
        },

        productCriteria() {
            const criteria = new Criteria(1, 25);

            criteria.addAssociation('options.group');
            criteria.addAssociation('tax');

            criteria.addFilter(
                Criteria.multi('OR', [
                    Criteria.equals('childCount', 0),
                    Criteria.equals('childCount', null),
                ]),
            );

            criteria.addFilter(Criteria.equals('visibilities.salesChannelId', this.salesChannelId));
            criteria.addFilter(Criteria.equals('active', true));

            return criteria;
        },
    },

    methods: {
        onItemChanged(newProductId) {
            const criteria = new Criteria(1, 1);
            criteria.addAssociation('tax');

            this.productRepository.get(newProductId, this.contextWithInheritance, criteria).then((newProduct) => {
                this.item.identifier = newProduct.id;
                this.item.label = newProduct.name;
                this.item.priceDefinition.price =
                    this.taxStatus === 'gross' ? newProduct.price[0].gross : newProduct.price[0].net;
                this.item.priceDefinition.type = this.lineItemPriceTypes.QUANTITY;
                this.item.price.taxRules[0].taxRate = newProduct.tax.taxRate;
                this.item.price.unitPrice = '...';
                this.item.price.totalPrice = '...';
                this.item.price.quantity = 1;
                this.item.unitPrice = '...';
                this.item.totalPrice = '...';
                this.item.precision = 2;
                this.item.priceDefinition.taxRules[0].taxRate = newProduct.tax.taxRate;
            });
        },
    },
};
