/**
 * @sw-package framework
 */

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default function initLanguageService() {
    Cicada.Application.addServiceProviderMiddleware('repositoryFactory', (repositoryFactory) => {
        // load the language when repositoryFactory is created
        // eslint-disable-next-line @typescript-eslint/no-unused-expressions
        Cicada.Application.getContainer('service').languageAutoFetchingService;

        return repositoryFactory;
    });
}
