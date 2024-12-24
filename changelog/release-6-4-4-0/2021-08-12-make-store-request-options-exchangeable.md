---
title: Make store request options exchangeable
issue: NEXT-12609
---
# Core
* Added abstract class `Cicada\Core\Framework\Store\Authentication\AbstractStoreRequestOptionsProvider`.
* Added `Cicada\Core\Framework\Store\Authentication\StoreRequestOptionsProvider` to provide header and query parameter for store requests.
* Added `Cicada\Core\Framework\Store\Authentication\FrwRequestOptionsProvider` to provide header and query parameters for first run wizard api.
* Added `Cicada\Core\Framework\Store\Services\InstanceService` to provide `cicadaVersion` and `instanceId`
* Added `Cicada\Core\Framework\Store\Authentication\LocaleProvider` to provide the locale of the current user in requests.
* Changed super class from `AbstractStoreController` to `AbstractController` for `FirstRunWizardController`.
* Changed super class from `AbstractStoreController` to `AbstractController` for `StoreController`.
* Changed behaviour of `FirstRunWizardClient::frwLogin` and `FirstRunWizardClient::upgradeAccessToken`. Both update the users store token now automatically.
* Changed behaviour of `StoreClient::loginWithCicadaId`. It updates the users store token now automatically.
* Changed return type of `Cicada\Core\Framework\Store\Authentication\AbstractAuthenticationProvider::getUserStoreToken()` from `string` to `?string`
* Changed return type of `Cicada\Core\Framework\Store\Authentication\AuthenticationProvider::getUserStoreToken()` from `string` to `?string`
* Removed `final` keyword of constructor for `Cicada\Core\Framework\Store\Services\StoreClient`
* Deprecated `Cicada\Core\Framework\Store\Services\StoreService::getDefaultQueryParameters`. Use `Cicada\Core\Framework\Store\Services\StoreService::getDefaultQueryParametersFromContext` instead.
* Deprecated `Cicada\Core\Framework\Store\Services\StoreService::getCicadaVersion`. Use `Cicada\Core\Framework\Store\Services\InstanceService::getCicadaVersion` instead.
* Deprecated `Cicada\Core\Framework\Store\Api\AbstractStoreController`. It will be removed without any replacement.
* Deprecated `Cicada\Core\Framework\Store\Authentication\AbstractStoreRequestOptionsProvider::getDefaultQueryParameters`. In the future this function takes an `Context` object as it's only parameter.
