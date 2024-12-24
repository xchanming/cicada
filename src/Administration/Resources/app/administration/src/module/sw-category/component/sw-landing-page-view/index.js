import template from './sw-landing-page-view.html.twig';

const { Mixin } = Cicada;

/**
 * @package inventory
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['acl'],

    mixins: [
        Mixin.getByName('placeholder'),
    ],

    props: {
        isLoading: {
            type: Boolean,
            required: true,
            default: false,
        },
    },

    computed: {
        landingPage() {
            return Cicada.State.get('swCategoryDetail').landingPage;
        },

        cmsPage() {
            return Cicada.Store.get('cmsPage').currentPage;
        },
    },
};
