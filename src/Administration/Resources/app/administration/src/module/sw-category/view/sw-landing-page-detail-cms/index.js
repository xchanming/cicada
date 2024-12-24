import template from './sw-landing-page-detail-cms.html.twig';
import './sw-landing-page-detail-cms.scss';

/**
 * @package inventory
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    props: {
        isLoading: {
            type: Boolean,
            required: true,
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
