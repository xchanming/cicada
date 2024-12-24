import template from './sw-landing-page-detail-base.html.twig';
import './sw-landing-page-detail-base.scss';

const { Mixin } = Cicada;
const { mapState, mapPropertyErrors } = Cicada.Component.getComponentHelper();

/**
 * @package inventory
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    mixins: [
        Mixin.getByName('placeholder'),
    ],

    props: {
        isLoading: {
            type: Boolean,
            required: true,
        },
    },

    computed: {
        ...mapState('swCategoryDetail', {
            customFieldSetsArray: (state) => {
                if (!state.customFieldSets) {
                    return [];
                }

                return state.customFieldSets;
            },
        }),

        ...mapPropertyErrors('landingPage', [
            'name',
            'url',
            'salesChannels',
        ]),

        landingPage() {
            return Cicada.State.get('swCategoryDetail').landingPage;
        },

        cmsPage() {
            return Cicada.Store.get('cmsPage').currentPage;
        },

        isLayoutSet() {
            return this.landingPage.cmsPageId !== null;
        },
    },
};
