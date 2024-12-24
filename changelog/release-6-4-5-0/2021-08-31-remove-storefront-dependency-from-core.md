---
title: Remove Storefront dependency from Core
issue: NEXT-8572
---
# API
* Changed route `api.custom.updateapi.finish` to only return redirect to administration if the administration is installed, otherwise status 204 (NO_CONTENT) will be returned
* Changed route `api.action.captcha.list` to be only available if storefront is installed, otherwise it will result in a 404
___
# Administration
* Deprecated `\Cicada\Administration\Service\AdminOrderCartService`, use `\Cicada\Core\Checkout\Cart\ApiOrderCartService` instead
___
# Core
* Added `\Cicada\Core\Checkout\Cart\ApiOrderCartService`
* Changed `\Cicada\Core\Framework\Api\Controller\SalesChannelProxyController` to use `ApiOrderCartService` instead of the deprecated `AdminOrderCartService`
* Added `\Cicada\Core\Checkout\Customer\Event\AddressListingCriteriaEvent`
* Changed `\Cicada\Core\Checkout\Customer\SalesChannel\ListAddressRoute` to additionally dispatch the new `\Cicada\Core\Checkout\Customer\Event\AddressListingCriteriaEvent`
* Added `\Cicada\Core\Content\ProductExport\Event\ProductExportContentTypeEvent`
* Changed `\Cicada\Core\Content\ProductExport\SalesChannel\ExportController` to additionally dispatch the new `\Cicada\Core\Content\ProductExport\Event\ProductExportContentTypeEvent`
* Changed `\Cicada\Core\DevOps\System\Command\SystemInstallCommand` to only execute tasks that are available in the installation
* Deprecated `\Cicada\Core\Framework\Adapter\Asset\ThemeAssetPackage`, use `\Cicada\Storefront\Theme\ThemeAssetPackage` instead
* Added `\Cicada\Core\Framework\Adapter\Twig\Extension\SwSanitizeTwigFilter`
* Changed `\Cicada\Core\Framework\App\AppUrlChangeResolver\UninstallAppsStrategy` to make dependency on `ThemeAppLifecycleHandler` optional
* Changed `\Cicada\Core\Framework\Store\Helper\PermissionCategorization` to use private constants, instead of depending on the storefront
* Changed `\Cicada\Core\Framework\Store\Services\ExtensionLoader` to make dependency on `theme.repository` optional
* Changed `\Cicada\Core\Framework\Store\Services\StoreAppLifecycleService` to make dependency on `theme.repository` optional
* Changed `\Cicada\Core\Framework\Update\Api\UpdateController` to only redirect to the administration, if the administration is installed on finish request
* Changed `\Cicada\Core\System\User\Recovery\UserRecoveryService` to fallback on `APP_URL` when generating the administration url
* Changed `\Cicada\Core\HttpKernel` to only use HttpCache if it is available (storefront is installed)
* Removed `\Cicada\Core\Framework\Api\Controller\CaptchaController`
___
# Storefront
* Deprecated `\Cicada\Storefront\Page\Address\Listing\AddressListingCriteriaEvent`, use `\Cicada\Core\Checkout\Customer\Event\AddressListingCriteriaEvent` instead
* Deprecated `\Cicada\Storefront\Event\ProductExportContentTypeEvent`, use `\Cicada\Core\Content\ProductExport\Event\ProductExportContentTypeEvent` instead
* Added `\Cicada\Storefront\Theme\ThemeAssetPackage`
* Removed `\Cicada\Storefront\Framework\Twig\Extension\SwSanitizeTwigFilter`
* Added `\Cicada\Storefront\Controller\Api\CaptchaController`
* Changed `\Cicada\Storefront\Framework\Cache\CacheResponseSubscriber` to add HttpCache-Annotation to cached core routes
___
# Upgrade Information
## Deprecation of AdminOrderCartService

The `\Cicada\Administration\Service\AdminOrderCartService` was deprecated and will be removed in v6.5.0.0, please use the newly added `\Cicada\Core\Checkout\Cart\ApiOrderCartService` instead. 

## Deprecation of Cicada\Storefront\Page\Address\Listing\AddressListingCriteriaEvent

The `\Cicada\Storefront\Page\Address\Listing\AddressListingCriteriaEvent` was deprecated and will be removed in v6.5.0.0, if you subscribed to the event please use the newly added `\Cicada\Core\Checkout\Customer\Event\AddressListingCriteriaEvent` instead.

## Deprecation of Cicada\Storefront\Event\ProductExportContentTypeEvent

The `\Cicada\Storefront\Event\ProductExportContentTypeEvent` was deprecated and will be removed in v6.5.0.0, if you subscribed to the event please use the newly added `\Cicada\Core\Content\ProductExport\Event\ProductExportContentTypeEvent` instead.

## Deprecation of Cicada\Core\Framework\Adapter\Asset\ThemeAssetPackage

The `\Cicada\Core\Framework\Adapter\Asset\ThemeAssetPackage` was deprecated and will be removed in v6.5.0.0, please use the newly added `\Cicada\Storefront\Theme\ThemeAssetPackage` instead. 
