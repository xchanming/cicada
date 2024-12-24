/*
 * @package inventory
 */

import template from './sw-product-packaging-form.html.twig';

const { Mixin } = Cicada;
const { mapPropertyErrors, mapState, mapGetters } = Cicada.Component.getComponentHelper();

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    mixins: [
        Mixin.getByName('placeholder'),
    ],

    props: {
        allowEdit: {
            type: Boolean,
            required: false,
            // eslint-disable-next-line vue/no-boolean-default
            default: true,
        },

        showSettingPackaging: {
            type: Boolean,
            required: false,
            // eslint-disable-next-line vue/no-boolean-default
            default: true,
        },
    },

    computed: {
        ...mapGetters('swProductDetail', [
            'isLoading',
        ]),

        ...mapState('swProductDetail', [
            'product',
            'parentProduct',
        ]),

        ...mapPropertyErrors('product', [
            'purchaseUnit',
            'referenceUnit',
            'packUnit',
            'PackUnitPlural',
            'width',
            'height',
            'length',
            'weight',
        ]),
    },
};
