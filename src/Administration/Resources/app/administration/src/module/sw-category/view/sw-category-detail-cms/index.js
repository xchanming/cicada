import template from './sw-category-detail-cms.html.twig';
import './sw-category-detail-cms.scss';

/**
 * @package inventory
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['acl'],

    props: {
        isLoading: {
            type: Boolean,
            required: true,
        },
    },

    computed: {
        category() {
            return Cicada.State.get('swCategoryDetail').category;
        },

        cmsPage() {
            return Cicada.Store.get('cmsPage').currentPage;
        },
    },
};
