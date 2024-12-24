---
title: Add symplify phpstan rules
issue: NEXT-25940
---
# Core
* Added symplify/phpstan-rules to the dev dependencies and activated some new rules for the CI.
* Deprecated class `\Cicada\Core\Framework\Adapter\Filesystem\Filesystem` it will be removed in v6.6.0.0 as it was unused.
* Deprecated class `\Cicada\Core\Framework\Struct\Serializer\StructDecoder` it will be removed in v6.6.0.0 as it was unused.
* Deprecated properties `id`, `name` and `quantity` in `\Cicada\Core\Content\Product\Cart\PurchaseStepsError` and `\Cicada\Core\Content\Product\Cart\ProductStockReachedError`, the properties will become private and natively typed in v6.6.0.0.
* Deprecated properties `redis` and `connection` in `\Cicada\Core\Checkout\Cart\Command\CartMigrateCommand`, those will become private and readonly in v6.6.0.0.
___
# Upgrade Information
## Fix method signatures to comply with parent class/interface signature
The following method signatures were changed to comply with the parent class/interface signature:
**Visibility changes:**
* Method `configure()` was changed from public to protected in:
  * `Cicada\Storefront\Theme\Command\ThemeCompileCommand`
* Method `execute()` was changed from public to protected in:
  * `Cicada\Core\Framework\Adapter\Asset\AssetInstallCommand`
  * `Cicada\Core\DevOps\System\Command\SystemDumpDatabaseCommand`
  * `Cicada\Core\DevOps\System\Command\SystemRestoreDatabaseCommand`
  * `Cicada\Core\DevOps\Docs\App\DocsAppEventCommand`
  * 
* Method `getExpectedClass()` was changed from public to protected in:
  * `Cicada\Storefront\Theme\ThemeSalesChannelCollection`
  * `Cicada\Core\Framework\Store\Struct\PluginRecommendationCollection`
  * `Cicada\Core\Framework\Store\Struct\PluginCategoryCollection`
  * `Cicada\Core\Framework\Store\Struct\LicenseDomainCollection`
  * `Cicada\Core\Framework\Store\Struct\PluginRegionCollection`
  * `Cicada\Core\Content\ImportExport\Processing\Mapping\UpdateByCollection`
  * `Cicada\Core\Content\ImportExport\Processing\Mapping\MappingCollection`
  * `Cicada\Core\Content\Product\Aggregate\ProductCrossSellingAssignedProducts\ProductCrossSellingAssignedProductsCollection`
  * `Cicada\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingCollection`
  * `Cicada\Core\Content\Product\SalesChannel\CrossSelling\CrossSellingElementCollection`
  * `Cicada\Core\Content\Product\SalesChannel\SalesChannelProductCollection`
  * `Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscountPrice\PromotionDiscountPriceCollection`
* Method `getParentDefinitionClass()` was changed from public to protected in:
  * `Cicada\Core\System\SalesChannel\Aggregate\SalesChannelAnalytics\SalesChannelAnalyticsDefinition`
  * `Cicada\Core\Content\ImportExport\ImportExportProfileTranslationDefinition`
  * `Cicada\Core\Content\Product\Aggregate\ProductCrossSellingAssignedProducts\ProductCrossSellingAssignedProductsDefinition`
  * `Cicada\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition`
  * `Cicada\Core\Content\Product\Aggregate\ProductFeatureSetTranslation\ProductFeatureSetTranslationDefinition`
  * `Cicada\Core\Checkout\Promotion\Aggregate\PromotionTranslation\PromotionTranslationDefinition`
* Method `getDecorated()` was changed from public to protected in:
  * `Cicada\Core\System\Country\SalesChannel\CachedCountryRoute`
  * `Cicada\Core\System\Country\SalesChannel\CachedCountryStateRoute`
* Method `getSerializerClass()` was changed from public to protected in:
  * `Cicada\Core\Framework\DataAbstractionLayer\Field\StateMachineStateField`

**Parameter type changes:**
* Changed parameter `$url` to `string` in:
  * `Cicada\Storefront\Framework\Cache\ReverseProxy\ReverseProxyCache#purge()`
* Changed parameter `$data` and `$format` to `string` in:
  * `Cicada\Core\Framework\Struct\Serializer\StructDecoder#decode()`
  * `Cicada\Core\Framework\Struct\Serializer\StructDecoder#supportsDecoding()`
  * `Cicada\Core\Framework\Api\Serializer\JsonApiDecoder#decode()`
  * `Cicada\Core\Framework\Api\Serializer\JsonApiDecoder#supportsDecoding()`
* Changed parameter `$storageName` and `$propertyName` to `string` in:
  * `Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields#__construct()`
* Changed parameter `$event` to `object` in:
  * `Cicada\Core\Framework\Event\NestedEventDispatcher#dispatch()`
* Changed parameter `$listener` to `callable` in:
  * `Cicada\Core\Framework\Event\NestedEventDispatcher#removeListener()`
  * `Cicada\Core\Framework\Event\NestedEventDispatcher#getListenerPriority()`
  * `Cicada\Core\Framework\Webhook\WebhookDispatcher#removeListener()`
  * `Cicada\Core\Framework\Webhook\WebhookDispatcher#getListenerPriority()`
* Changed parameter `$constraints` to `Symfony\Component\Validator\Constraint|array|null` in:
  * `Cicada\Core\Framework\Validation\HappyPathValidator#validate()`
* Changed parameter `$object` to `object`, `$propertyName` to `string`, `$groups` to `string|Symfony\Component\Validator\Constraints\GroupSequence|array|null` and `$objectOrClass` to `object|string` in:
  * `Cicada\Core\Framework\Validation\HappyPathValidator#validateProperty()`
  * `Cicada\Core\Framework\Validation\HappyPathValidator#validatePropertyValue()`
* Changed parameter `$record` to `iterable` in:
  * `Cicada\Core\Content\ImportExport\Processing\Pipe\EntityPipe#in()`
* Changed parameter `$warmupDir` to `string` in:
  * `Cicada\Core\Kernel#reboot()`

