import template from './sw-category-detail-seo.html.twig';
import './sw-category-detail-seo.scss';

/**
 * @sw-package discovery
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
    },
};
