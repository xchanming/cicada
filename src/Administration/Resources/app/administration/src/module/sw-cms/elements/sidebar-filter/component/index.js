import template from './sw-cms-el-sidebar-filter.html.twig';
import './sw-cms-el-sidebar-filter.scss';

/**
 * @private
 * @sw-package discovery
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    mixins: [
        Cicada.Mixin.getByName('cms-element'),
    ],

    computed: {
        componentClasses() {
            return {
                'is--disabled': this.disabled,
            };
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('sidebar-filter');
        },
    },
};
