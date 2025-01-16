/**
 * @sw-package after-sales
 */

import template from './sw-mail-header-footer-create.html.twig';

const utils = Cicada.Utils;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    beforeRouteEnter(to, from, next) {
        if (to.name.includes('sw.mail.template.create_head_foot') && !to.params.id) {
            to.params.id = utils.createId();
        }

        next();
    },

    methods: {
        createdComponent() {
            if (!Cicada.State.getters['context/isSystemDefaultLanguage']) {
                Cicada.State.commit('context/resetLanguageToDefault');
            }

            if (this.$route.params.id) {
                this.mailHeaderFooter = this.mailHeaderFooterRepository.create(Cicada.Context.api, this.$route.params.id);
            }

            this.isLoading = false;
        },

        saveFinish() {
            this.isSaveSuccessful = false;
            this.$router.push({
                name: 'sw.mail.template.detail_head_foot',
                params: { id: this.$route.params.id },
            });
        },

        onSave() {
            this.isLoading = true;
            this.$super('onSave');
        },
    },
};
