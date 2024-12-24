/**
 * @package buyers-experience
 */
import template from './sw-settings-country-create.html.twig';

const utils = Cicada.Utils;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    beforeRouteEnter(to, from, next) {
        if (to.name.includes('sw.settings.country.create') && !to.params.id) {
            to.params.id = utils.createId();
        }

        next();
    },

    methods: {
        createdComponent() {
            Cicada.Context.api.languageId = Cicada.Context.api.systemLanguageId;

            if (this.$route.params.id) {
                this.country = this.countryRepository.create(Cicada.Context.api, this.$route.params.id);
                this.country.customerTax = {
                    amount: 0,
                    currencyId: Cicada.Context.app.systemCurrencyId,
                    enabled: false,
                };
                this.country.companyTax = {
                    amount: 0,
                    currencyId: Cicada.Context.app.systemCurrencyId,
                    enabled: false,
                };
                this.countryId = this.country.id;
                this.countryStateRepository = this.repositoryFactory.create(
                    this.country.states.entity,
                    this.country.states.source,
                );
            }
        },

        saveFinish() {
            this.isSaveSuccessful = false;
            this.$router.push({
                name: 'sw.settings.country.detail',
                params: { id: this.country.id },
            });
        },
    },
};
