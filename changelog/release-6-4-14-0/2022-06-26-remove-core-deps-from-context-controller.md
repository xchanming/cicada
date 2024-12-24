---
title: Remove core dependencies from ContextController
issue: NEXT-21967
author: Stefan Sluiter
author_email: s.sluiter@cicada.com
author_github: ssltg
---
# Core
* Added `Cicada\Core\Checkout\Customer\SalesChannel\AbstractChangeLanguageRoute`
* Added `Cicada\Core\Checkout\Customer\SalesChannel\ChangeLanguageRoute`
* Added new store-api route `/account/change-language`
* Changed `\Cicada\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute::switchContext` to check return the new domain on language change.
* Added `getRedirectUrl` to `Cicada\Core\System\SalesChannel\ContextTokenResponse` to hold the redirectUrl in a token change if necessary. 
___
# Storefront
* Changed `Cicada\Storefront\Controller\ContextController` to use `ChangeLanguageRoute` and `ContextSwitchRoute` instead of repositories.
* Deprecated `ChangeLanguageRoute` in `Cicada\Storefront\Controller\ContextController`. This will be removed in v6.5.0.0.
* Deprecated the automatic change of the customers language on the change of the storefront language in `\Cicada\Storefront\Controller\ContextController::switchLanguage`
