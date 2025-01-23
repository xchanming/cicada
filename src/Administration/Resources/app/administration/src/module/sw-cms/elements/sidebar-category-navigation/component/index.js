import template from './sw-cms-el-category-navigation.html.twig';
import './sw-cms-el-category-navigation.scss';

/**
 * @private
 * @sw-package discovery
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    mixins: [
        Cicada.Mixin.getByName('cms-element'),
        Cicada.Mixin.getByName('placeholder'),
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('category-navigation');
        },
    },
};
