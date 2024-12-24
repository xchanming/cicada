---
title: Remove Api Version
issue: NEXT-10665
---

# Administration

* Deleted following file `src/Administration/Resources/app/administration/test/core/factory/http.factory.spec.js`
* Removed `context/setApiApiVersion` from `Cicada.State`
* Removed `getApiVersion` from API Service
* Changed Cypress tests to consider `apiPath` env variable
___
# Core
___
* Removed parameter `$version` from method `Cicada\Core\Checkout\Order\Api\OrderActionController:orderStateTransition`
* Removed parameter `$version` from method `Cicada\Core\Checkout\Order\Api\OrderActionController:orderTransactionStateTransition`
* Removed parameter `$version` from method `Cicada\Core\Checkout\Order\Api\OrderActionController:orderDeliveryStateTransition`
* Removed parameter `$version` from method `Cicada\Core\Content\ImportExport\Controller\ImportExportActionController:initiate`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\DefinitionService:generate`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\DefinitionService:getSchema`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\EntitySchemaGenerator:supports`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\EntitySchemaGenerator:generate`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\EntitySchemaGenerator:getSchema`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\OpenApi\OpenApiDefinitionSchemaBuilder:getSchemaByDefinition`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\OpenApi\OpenApiSchemaBuilder:enrich`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\OpenApi3Generator:supports`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\OpenApi3Generator:generate`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\OpenApi3Generator:getSchema`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\StoreApiGenerator:supports`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\StoreApiGenerator:generate`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\ApiDefinition\Generator\StoreApiGenerator:getSchema`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\Controller\ApiController:compositeSearch`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\Controller\ApiController:clone`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\Controller\ApiController:createVersion`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\Controller\ApiController:mergeVersion`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\Controller\InfoController:info`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\Controller\InfoController:openApiSchema`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\Controller\InfoController:entitySchema`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\Controller\InfoController:infoHtml`
* Removed parameter `$version` from method `Cicada\Core\Framework\Api\Controller\SyncController:sync`
* Removed method `Cicada\Core\Framework\Api\Converter\ApiConverter:getApiVersion`
* Removed method `Cicada\Core\Framework\Api\Converter\ApiConverter:isDeprecated`
* Removed method `Cicada\Core\Framework\Api\Converter\ApiConverter:isFromFuture`
* Removed method `Cicada\Core\Framework\Api\Converter\ApiConverter:getDeprecations`
* Removed method `Cicada\Core\Framework\Api\Converter\ApiConverter:getNewFields`
* Removed method `Cicada\Core\Framework\Api\Converter\ApiVersionConverter:isAllowed`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\Api\Converter\ApiVersionConverter:convertEntity`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\Api\Converter\ApiVersionConverter:convertPayload`
* Removed method `Cicada\Core\Framework\Api\Converter\ApiVersionConverter:validateEntityPath`
* Removed method `Cicada\Core\Framework\Api\Converter\ApiVersionConverter:convertCriteria`
* Removed method `Cicada\Core\Framework\Api\Converter\ApiVersionConverter:ignoreDeprecations`
* Removed method `Cicada\Core\Framework\Api\Converter\ConverterRegistry:isDeprecated`
* Removed method `Cicada\Core\Framework\Api\Converter\ConverterRegistry:isFromFuture`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\Api\Converter\ConverterRegistry:convert`
* Changed return value from method `Cicada\Core\Framework\Api\Converter\ConverterRegistry:getConverters` from `array` to `iterable`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\Api\Converter\ConverterRegistry:getConverters`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\Api\Converter\DefaultApiConverter:convert`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\Api\Converter\DefaultApiConverter:isDeprecated`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\Api\Converter\DefaultApiConverter:getDeprecations`
* Removed method `Cicada\Core\Framework\Api\Response\Type\JsonFactoryBase:getVersion`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\Api\Serializer\JsonApiEncoder:encode`
* Removed method `Cicada\Core\Framework\Api\Serializer\JsonApiEncodingResult:getApiVersion`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\Api\Serializer\JsonEntityEncoder:encode`
* Removed method `Cicada\Core\Framework\Api\Sync\SyncOperation:getApiVersion`
* Removed parameter `$apiVersion` from method `Cicada\Core\Framework\DataAbstractionLayer\Search\CompositeEntitySearcher:search`
* Removed parameter `$version` from method `Cicada\Core\Framework\Plugin\Api\PluginController:deletePlugin`
* Removed parameter `$version` from method `Cicada\Core\Framework\Plugin\Api\PluginController:installPlugin`
* Removed parameter `$version` from method `Cicada\Core\Framework\Plugin\Api\PluginController:uninstallPlugin`
* Removed parameter `$version` from method `Cicada\Core\Framework\Plugin\Api\PluginController:activatePlugin`
* Removed parameter `$version` from method `Cicada\Core\Framework\Plugin\Api\PluginController:deactivatePlugin`
* Removed method `Cicada\Core\Framework\Test\Api\Sync\SyncServiceTest:testWriteDeprecatedFieldLeadsToError`
* Removed method `Cicada\Core\Framework\Test\Api\Sync\SyncServiceTest:testWriteDeprecatedEntityLeadsToError`
* Removed parameter `$apiVersion` from method `Cicada\Core\System\SalesChannel\Api\StructEncoder:encode`
* Removed parameter `$version` from method `Cicada\Core\System\SalesChannel\SalesChannel\StoreApiInfoController:info`
* Removed parameter `$version` from method `Cicada\Core\System\SalesChannel\SalesChannel\StoreApiInfoController:openApiSchema`
* Removed parameter `$version` from method `Cicada\Core\System\SalesChannel\SalesChannel\StoreApiInfoController:infoHtml`
* Deleted following classes:
    * `Cicada\Core\Framework\Api\ApiVersion\ApiVersionSubscriber`
    * `Cicada\Core\Framework\Api\Converter\Exceptions\ApiConversionNotAllowedException`
    * `Cicada\Core\Framework\Api\Converter\Exceptions\QueryFutureEntityException`
    * `Cicada\Core\Framework\Api\Converter\Exceptions\QueryFutureFieldException`
    * `Cicada\Core\Framework\Api\Converter\Exceptions\QueryRemovedEntityException`
    * `Cicada\Core\Framework\Api\Converter\Exceptions\QueryRemovedFieldException`
    * `Cicada\Core\Framework\Api\Converter\Exceptions\WriteFutureFieldException`
    * `Cicada\Core\Framework\Api\Converter\Exceptions\WriteRemovedFieldException`
    * `Cicada\Core\Framework\Test\Api\ApiVersion\ApiVersionSubscriberTest`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\ApiVersioningV2Test`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\ApiVersioningV3Test`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\ApiVersioningV4Test`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\ApiConverter\ConverterV2`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\ApiConverter\ConverterV3`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\ApiConverter\ConverterV4`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v1\BundleCollection`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v1\BundleDefinition`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v1\BundleEntity`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v2\BundleCollection`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v2\BundleDefinition`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v2\BundleEntity`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v3\Aggregate\BundlePrice\BundlePriceDefinition`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v3\Aggregate\BundleTanslation\BundleTranslationDefinition`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v3\BundleCollection`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v3\BundleDefinition`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v3\BundleEntity`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v4\Aggregate\BundlePrice\BundlePriceDefinition`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v4\Aggregate\BundleTanslation\BundleTranslationDefinition`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v4\BundleCollection`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v4\BundleDefinition`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Entities\v4\BundleEntity`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Migrations\Migration1571753490v1`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Migrations\Migration1571754409v2`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Migrations\Migration1571832058v3`
    * `Cicada\Core\Framework\Test\Api\ApiVersioning\fixtures\Migrations\Migration1572528079v4`
    * `Cicada\Core\Framework\Test\Api\Converter\ApiVersionConverterTest`
    * `Cicada\Core\Framework\Test\Api\Converter\DefaultApiConverterTest`
    * `Cicada\Core\Framework\Test\Api\Converter\fixtures\NewEntityDefinition`

