import template from './sw-cms-el-form.html.twig';
import contact from './templates/form-contact/index';
import newsletter from './templates/form-newsletter/index';
import './sw-cms-el-form.scss';

const { Mixin } = Cicada;

/**
 * @private
 * @package buyers-experience
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    components: {
        contact,
        newsletter,
    },

    computed: {
        selectedForm() {
            return this.element.config.type.value;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('form');
        },
    },
};
