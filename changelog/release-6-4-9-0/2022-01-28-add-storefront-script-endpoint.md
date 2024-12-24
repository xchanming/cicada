---
title: Add storefront endpoints for app scripts
issue: NEXT-19568
---
# Storefront
* Added `\Cicada\Storefront\Framework\Script\Api\StorefrontHook` to provide the functionality to add custom endpoints in the storefront via scripts.
* Added `\Cicada\Core\Framework\Script\Api\ScriptResponseFactoryFacade` to create responses for custom-endpoint scripts.
* Added `\Cicada\Core\System\SalesChannel\SalesChannelContext::ensureLoggedIn()` helper method, to throw a `CustomerNotLoggedInException` if the customer is not logged in.
