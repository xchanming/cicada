import template from './sw-cms-el-html.html.twig';
import './sw-cms-el-html.scss';

const { Mixin } = Cicada;

/**
 * @private
 * @sw-package discovery
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    data() {
        return {
            editorConfig: {
                highlightActiveLine: false,
                cursorStyle: 'slim',
                highlightGutterLine: false,
                showFoldWidgets: false,
            },
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('html');
        },
    },
};
