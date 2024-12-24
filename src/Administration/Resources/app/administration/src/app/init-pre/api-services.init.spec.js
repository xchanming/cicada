/**
 * @package admin
 */
import initializeApiServices from 'src/app/init-pre/api-services.init';

describe('src/app/init-pre/api-services.init.ts', () => {
    /**
     * [
     *         'aclApiService',
     *         'appActionButtonService',
     *         'appCmsBlocks',
     *         'appModulesService',
     *         'appUrlChangeService',
     *         'businessEventService',
     *         'cacheApiService',
     *         'calculate-price',
     *         'cartStoreService',
     *         'checkoutStoreService',
     *         'configService',
     *         'customSnippetApiService',
     *         'customerGroupRegistrationService',
     *         'customerValidationService',
     *         'documentService',
     *         'excludedSearchTermService',
     *         'extensionSdkService',
     *         'firstRunWizardService',
     *         'flowActionService',
     *         'importExportService',
     *         'integrationService',
     *         'knownIpsService',
     *         'languagePluginService',
     *         'mailService',
     *         'mediaFolderService',
     *         'mediaService',
     *         'messageQueueService',
     *         'notificationsService',
     *         'numberRangeService',
     *         'orderDocumentApiService',
     *         'orderStateMachineService',
     *         'orderService',
     *         'productExportService',
     *         'productStreamPreviewService',
     *         'promotionSyncService',
     *         'recommendationsService',
     *         'ruleConditionsConfigApiService',
     *         'salesChannelService',
     *         'scheduledTaskService',
     *         'searchService',
     *         'seoUrlTemplateService',
     *         'seoUrlService',
     *         'snippetSetService',
     *         'snippetService',
     *         'stateMachineService',
     *         'contextStoreService',
     *         'storeService',
     *         'syncService',
     *         'systemConfigApiService',
     *         'tagApiService',
     *         'updateService',
     *         'userActivityApiService',
     *         'userConfigService',
     *         'userInputSanitizeService',
     *         'userRecoveryService',
     *         'userValidationService',
     *         'userService'
     *       ]
     */

    it('should initialize the api services', () => {
        expect(Cicada.Service('aclApiService')).toBeUndefined();
        expect(Cicada.Service('appActionButtonService')).toBeUndefined();
        expect(Cicada.Service('appCmsBlocks')).toBeUndefined();
        expect(Cicada.Service('appModulesService')).toBeUndefined();
        expect(Cicada.Service('appUrlChangeService')).toBeUndefined();
        expect(Cicada.Service('businessEventService')).toBeUndefined();
        expect(Cicada.Service('cacheApiService')).toBeUndefined();
        expect(Cicada.Service('calculate-price')).toBeUndefined();
        expect(Cicada.Service('cartStoreService')).toBeUndefined();
        expect(Cicada.Service('checkoutStoreService')).toBeUndefined();
        expect(Cicada.Service('configService')).toBeUndefined();
        expect(Cicada.Service('customSnippetApiService')).toBeUndefined();
        expect(Cicada.Service('customerGroupRegistrationService')).toBeUndefined();
        expect(Cicada.Service('customerValidationService')).toBeUndefined();
        expect(Cicada.Service('documentService')).toBeUndefined();
        expect(Cicada.Service('excludedSearchTermService')).toBeUndefined();
        expect(Cicada.Service('extensionSdkService')).toBeUndefined();
        expect(Cicada.Service('firstRunWizardService')).toBeUndefined();
        expect(Cicada.Service('flowActionService')).toBeUndefined();
        expect(Cicada.Service('importExportService')).toBeUndefined();
        expect(Cicada.Service('integrationService')).toBeUndefined();
        expect(Cicada.Service('knownIpsService')).toBeUndefined();
        expect(Cicada.Service('languagePluginService')).toBeUndefined();
        expect(Cicada.Service('mailService')).toBeUndefined();
        expect(Cicada.Service('mediaFolderService')).toBeUndefined();
        expect(Cicada.Service('mediaService')).toBeUndefined();
        expect(Cicada.Service('messageQueueService')).toBeUndefined();
        expect(Cicada.Service('notificationsService')).toBeUndefined();
        expect(Cicada.Service('numberRangeService')).toBeUndefined();
        expect(Cicada.Service('orderDocumentApiService')).toBeUndefined();
        expect(Cicada.Service('orderStateMachineService')).toBeUndefined();
        expect(Cicada.Service('orderService')).toBeUndefined();
        expect(Cicada.Service('productExportService')).toBeUndefined();
        expect(Cicada.Service('productStreamPreviewService')).toBeUndefined();
        expect(Cicada.Service('promotionSyncService')).toBeUndefined();
        expect(Cicada.Service('recommendationsService')).toBeUndefined();
        expect(Cicada.Service('ruleConditionsConfigApiService')).toBeUndefined();
        expect(Cicada.Service('salesChannelService')).toBeUndefined();
        expect(Cicada.Service('scheduledTaskService')).toBeUndefined();
        expect(Cicada.Service('searchService')).toBeUndefined();
        expect(Cicada.Service('seoUrlTemplateService')).toBeUndefined();
        expect(Cicada.Service('seoUrlService')).toBeUndefined();
        expect(Cicada.Service('snippetSetService')).toBeUndefined();
        expect(Cicada.Service('snippetService')).toBeUndefined();
        expect(Cicada.Service('stateMachineService')).toBeUndefined();
        expect(Cicada.Service('contextStoreService')).toBeUndefined();
        expect(Cicada.Service('storeService')).toBeUndefined();
        expect(Cicada.Service('syncService')).toBeUndefined();
        expect(Cicada.Service('systemConfigApiService')).toBeUndefined();
        expect(Cicada.Service('tagApiService')).toBeUndefined();
        expect(Cicada.Service('updateService')).toBeUndefined();
        expect(Cicada.Service('userActivityApiService')).toBeUndefined();
        expect(Cicada.Service('userConfigService')).toBeUndefined();
        expect(Cicada.Service('userInputSanitizeService')).toBeUndefined();
        expect(Cicada.Service('userRecoveryService')).toBeUndefined();
        expect(Cicada.Service('userValidationService')).toBeUndefined();
        expect(Cicada.Service('userService')).toBeUndefined();

        initializeApiServices();

        expect(Cicada.Service('aclApiService')).toBeDefined();
        expect(Cicada.Service('appActionButtonService')).toBeDefined();
        expect(Cicada.Service('appCmsBlocks')).toBeDefined();
        expect(Cicada.Service('appModulesService')).toBeDefined();
        expect(Cicada.Service('appUrlChangeService')).toBeDefined();
        expect(Cicada.Service('businessEventService')).toBeDefined();
        expect(Cicada.Service('cacheApiService')).toBeDefined();
        expect(Cicada.Service('calculate-price')).toBeDefined();
        expect(Cicada.Service('cartStoreService')).toBeDefined();
        expect(Cicada.Service('checkoutStoreService')).toBeDefined();
        expect(Cicada.Service('configService')).toBeDefined();
        expect(Cicada.Service('customSnippetApiService')).toBeDefined();
        expect(Cicada.Service('customerGroupRegistrationService')).toBeDefined();
        expect(Cicada.Service('customerValidationService')).toBeDefined();
        expect(Cicada.Service('documentService')).toBeDefined();
        expect(Cicada.Service('excludedSearchTermService')).toBeDefined();
        expect(Cicada.Service('extensionSdkService')).toBeDefined();
        expect(Cicada.Service('firstRunWizardService')).toBeDefined();
        expect(Cicada.Service('flowActionService')).toBeDefined();
        expect(Cicada.Service('importExportService')).toBeDefined();
        expect(Cicada.Service('integrationService')).toBeDefined();
        expect(Cicada.Service('knownIpsService')).toBeDefined();
        expect(Cicada.Service('languagePluginService')).toBeDefined();
        expect(Cicada.Service('mailService')).toBeDefined();
        expect(Cicada.Service('mediaFolderService')).toBeDefined();
        expect(Cicada.Service('mediaService')).toBeDefined();
        expect(Cicada.Service('messageQueueService')).toBeDefined();
        expect(Cicada.Service('notificationsService')).toBeDefined();
        expect(Cicada.Service('numberRangeService')).toBeDefined();
        expect(Cicada.Service('orderDocumentApiService')).toBeDefined();
        expect(Cicada.Service('orderStateMachineService')).toBeDefined();
        expect(Cicada.Service('orderService')).toBeDefined();
        expect(Cicada.Service('productExportService')).toBeDefined();
        expect(Cicada.Service('productStreamPreviewService')).toBeDefined();
        expect(Cicada.Service('promotionSyncService')).toBeDefined();
        expect(Cicada.Service('recommendationsService')).toBeDefined();
        expect(Cicada.Service('ruleConditionsConfigApiService')).toBeDefined();
        expect(Cicada.Service('salesChannelService')).toBeDefined();
        expect(Cicada.Service('scheduledTaskService')).toBeDefined();
        expect(Cicada.Service('searchService')).toBeDefined();
        expect(Cicada.Service('seoUrlTemplateService')).toBeDefined();
        expect(Cicada.Service('seoUrlService')).toBeDefined();
        expect(Cicada.Service('snippetSetService')).toBeDefined();
        expect(Cicada.Service('snippetService')).toBeDefined();
        expect(Cicada.Service('stateMachineService')).toBeDefined();
        expect(Cicada.Service('contextStoreService')).toBeDefined();
        expect(Cicada.Service('storeService')).toBeDefined();
        expect(Cicada.Service('syncService')).toBeDefined();
        expect(Cicada.Service('systemConfigApiService')).toBeDefined();
        expect(Cicada.Service('tagApiService')).toBeDefined();
        expect(Cicada.Service('updateService')).toBeDefined();
        expect(Cicada.Service('userActivityApiService')).toBeDefined();
        expect(Cicada.Service('userConfigService')).toBeDefined();
        expect(Cicada.Service('userInputSanitizeService')).toBeDefined();
        expect(Cicada.Service('userRecoveryService')).toBeDefined();
        expect(Cicada.Service('userValidationService')).toBeDefined();
        expect(Cicada.Service('userService')).toBeDefined();
    });
});
