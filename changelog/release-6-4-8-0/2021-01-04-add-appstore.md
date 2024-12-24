---
title: Add Appstore
issue: NEXT-12608
---

# Core

* Added following new classes:
    * `Cicada\Core\Framework\Store\Api\ExtensionStoreActionsController`
    * `Cicada\Core\Framework\Store\Api\ExtensionStoreCategoryController`
    * `Cicada\Core\Framework\Store\Api\ExtensionStoreDataController`
    * `Cicada\Core\Framework\Store\Api\ExtensionStoreLicensesController`
    * `Cicada\Core\Framework\Store\Authentication\AbstractAuthenticationProvider`
    * `Cicada\Core\Framework\Store\Authentication\AuthenticationProvider`
    * `Cicada\Core\Framework\Store\Exception\ExtensionInstallException`
    * `Cicada\Core\Framework\Store\Exception\ExtensionNotFoundException`
    * `Cicada\Core\Framework\Store\Exception\ExtensionThemeStillInUseException`
    * `Cicada\Core\Framework\Store\Exception\InvalidExtensionIdException`
    * `Cicada\Core\Framework\Store\Exception\InvalidExtensionRatingValueException`
    * `Cicada\Core\Framework\Store\Exception\InvalidVariantIdException`
    * `Cicada\Core\Framework\Store\Exception\LicenseNotFoundException`
    * `Cicada\Core\Framework\Store\Exception\VariantTypesNotAllowedException`
    * `Cicada\Core\Framework\Store\Helper\PermissionCategorization`
    * `Cicada\Core\Framework\Store\Search\EqualsFilterStruct`
    * `Cicada\Core\Framework\Store\Search\ExtensionCriteria`
    * `Cicada\Core\Framework\Store\Search\FilterStruct`
    * `Cicada\Core\Framework\Store\Search\MultiFilterStruct`
    * `Cicada\Core\Framework\Store\Services\AbstractExtensionDataProvider`
    * `Cicada\Core\Framework\Store\Services\AbstractExtensionStoreLicensesService`
    * `Cicada\Core\Framework\Store\Services\AbstractStoreAppLifecycleService`
    * `Cicada\Core\Framework\Store\Services\AbstractStoreCategoryProvider`
    * `Cicada\Core\Framework\Store\Services\ExtensionDataProvider`
    * `Cicada\Core\Framework\Store\Services\ExtensionDownloader`
    * `Cicada\Core\Framework\Store\Services\ExtensionLifecycleService`
    * `Cicada\Core\Framework\Store\Services\ExtensionLoader`
    * `Cicada\Core\Framework\Store\Services\ExtensionStoreLicensesService`
    * `Cicada\Core\Framework\Store\Services\LicenseLoader`
    * `Cicada\Core\Framework\Store\Services\StoreAppLifecycleService`
    * `Cicada\Core\Framework\Store\Services\StoreCategoryProvider`
    * `Cicada\Core\Framework\Store\Struct\BinaryCollection`
    * `Cicada\Core\Framework\Store\Struct\BinaryStruct`
    * `Cicada\Core\Framework\Store\Struct\CartPositionCollection`
    * `Cicada\Core\Framework\Store\Struct\CartPositionStruct`
    * `Cicada\Core\Framework\Store\Struct\CartStruct`
    * `Cicada\Core\Framework\Store\Struct\DiscountCampaignStruct`
    * `Cicada\Core\Framework\Store\Struct\ExtensionCollection`
    * `Cicada\Core\Framework\Store\Struct\ExtensionStruct`
    * `Cicada\Core\Framework\Store\Struct\FaqCollection`
    * `Cicada\Core\Framework\Store\Struct\FaqStruct`
    * `Cicada\Core\Framework\Store\Struct\ImageCollection`
    * `Cicada\Core\Framework\Store\Struct\ImageStruct`
    * `Cicada\Core\Framework\Store\Struct\LicenseCollection`
    * `Cicada\Core\Framework\Store\Struct\LicenseStruct`
    * `Cicada\Core\Framework\Store\Struct\PermissionCollection`
    * `Cicada\Core\Framework\Store\Struct\PermissionStruct`
    * `Cicada\Core\Framework\Store\Struct\ReviewCollection`
    * `Cicada\Core\Framework\Store\Struct\ReviewStruct`
    * `Cicada\Core\Framework\Store\Struct\ReviewSummaryStruct`
    * `Cicada\Core\Framework\Store\Struct\StoreCategoryCollection`
    * `Cicada\Core\Framework\Store\Struct\StoreCategoryStruct`
    * `Cicada\Core\Framework\Store\Struct\StoreCollection`
    * `Cicada\Core\Framework\Store\Struct\StoreStruct`
    * `Cicada\Core\Framework\Store\Struct\VariantCollection`
    * `Cicada\Core\Framework\Store\Struct\VariantStruct`
    * `Cicada\Core\Framework\Test\Store\Api\ExtensionStoreActionsControllerTest`
    * `Cicada\Core\Framework\Test\Store\Api\ExtensionStoreCategoryControllerTest`
    * `Cicada\Core\Framework\Test\Store\Api\ExtensionStoreDataControllerTest`
    * `Cicada\Core\Framework\Test\Store\Api\ExtensionStoreLicensesControllerTest`
    * `Cicada\Core\Framework\Test\Store\Authentication\AuthenticationProviderTest`
    * `Cicada\Core\Framework\Test\Store\Search\ExtensionCriteriaTest`
    * `Cicada\Core\Framework\Test\Store\Search\FilterStructClassTest`
    * `Cicada\Core\Framework\Test\Store\Service\ExtensionDataProviderTest`
    * `Cicada\Core\Framework\Test\Store\Service\ExtensionDownloaderTest`
    * `Cicada\Core\Framework\Test\Store\Service\ExtensionLifecycleServiceTest`
    * `Cicada\Core\Framework\Test\Store\Service\ExtensionLoaderTest`
    * `Cicada\Core\Framework\Test\Store\Service\ExtensionStoreLicensesServiceTest`
    * `Cicada\Core\Framework\Test\Store\Service\LicenseLoaderTest`
    * `Swag\SaasRufus\Test\Core\Framework\Extension\Service\StoreCategoryProviderTest`
    * `Swag\SaasRufus\Test\Core\Framework\Extension\Struct\ExtensionStructTest`
    * `Swag\SaasRufus\Test\Core\Framework\Extension\Struct\PermissionCollectionTest`
    * `Swag\SaasRufus\Test\Core\Framework\Extension\Struct\ReviewStructTest`
    * `AppStoreTestPlugin\AppStoreTestPlugin`
* Added new method `Cicada\Core\Framework\App\Lifecycle\AppLoader:deleteApp`
* Added new parameter `$type` to method `Cicada\Core\Framework\Plugin\PluginExtractor:extract`
* Changed return value from method `Cicada\Core\Framework\Plugin\PluginManagementService:extractPluginZip` from `void` to `string`
* Added new method `Cicada\Core\Framework\Plugin\PluginZipDetector:isApp`
* Added new method `Cicada\Core\Framework\Store\Api\StoreController:categoriesAction`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:getCategories`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:listExtensions`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:listListingFilters`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:extensionDetail`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:extensionDetailReviews`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:createCart`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:orderCart`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:cancelSubscription`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:createRating`
* Added new method `Cicada\Core\Framework\Store\Services\StoreClient:getLicenses`
* Added new method `Cicada\Core\Framework\Store\Services\StoreService:getLanguageByContext`
* Added new method `Cicada\Core\Framework\Store\Struct\PluginDownloadDataStruct:getType`
