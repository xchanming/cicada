---
title: Deprecate setTwig in Storefront Controller
issue: NEXT-39782
---

# Storefront
* Deprecated `Cicada\Storefront\Controller\StorefrontController::setTwig`
* Deprecated `\Cicada\Storefront\Framework\Captcha\Exception\CaptchaInvalidException`, use `\Cicada\Storefront\Framework\Captcha\CaptchaException::invalid` instead
___
# Upgrade Information

## setTwig in Storefront Controller deprecated

The method `Cicada\Storefront\Controller\StorefrontController::setTwig` is deprecated and will be removed in 6.7.0, you can remove the `setTwig` call from your DI config, no further change is required.

___

# Next Major Version Changes

## setTwig in Storefront Controller removed

The method `Cicada\Storefront\Controller\StorefrontController::setTwig` has been removed, you can remove the `setTwig` call from your DI config, no further change is required.

