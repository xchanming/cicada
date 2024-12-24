/**
 * @package admin
 */

let isInitialized = false;

/**
 * @private
 */
export default function LanguageAutoFetchingService() {
    if (isInitialized) return;
    isInitialized = true;

    // initial loading of the language
    loadLanguage(Cicada.Context.api.languageId);

    // load the language Entity
    async function loadLanguage(newLanguageId) {
        const languageRepository = Cicada.Service('repositoryFactory').create('language');
        const newLanguage = await languageRepository.get(newLanguageId, {
            ...Cicada.Context.api,
            inheritance: true,
        });

        Cicada.State.commit('context/setApiLanguage', newLanguage);
    }

    // watch for changes of the languageId
    Cicada.State.watch(
        (state) => state.context.api.languageId,
        (newValue, oldValue) => {
            if (newValue === oldValue) return;

            loadLanguage(newValue);
        },
    );
}
