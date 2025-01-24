/*
 * @sw-package inventory
 */

import template from './sw-property-create.html.twig';

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    data() {
        return {
            newId: null,
        };
    },

    computed: {
        assetFilter() {
            return Cicada.Filter.getByName('asset');
        },
    },

    methods: {
        createdComponent() {
            if (!Cicada.State.getters['context/isSystemDefaultLanguage']) {
                Cicada.Context.api.languageId = Cicada.Context.api.systemLanguageId;
            }

            this.propertyGroup = this.propertyRepository.create();
            this.propertyGroup.sortingType = 'alphanumeric';
            this.propertyGroup.displayType = 'text';
            this.propertyGroup.position = 1;
            this.propertyGroup.filterable = true;
            this.propertyGroup.visibleOnProductDetailPage = true;
            this.newId = this.propertyGroup.id;

            this.isLoading = false;
        },

        saveFinish() {
            this.isSaveSuccessful = false;
            this.$router.push({
                name: 'sw.property.detail',
                params: { id: this.newId },
            });
        },

        onSave() {
            this.$super('onSave');
        },
    },
};
