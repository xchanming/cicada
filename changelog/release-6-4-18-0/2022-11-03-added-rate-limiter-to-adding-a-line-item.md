---
title: Added rate limiter to adding a line item
issue: NEXT-23422
author: Michel Bade
author_email: m.bade@cicada.com
author_github: @cyl3x
---
# Core
* Added `cart_add_line_item` to rate limiter configuration in `Shopwar\Core\Framework\Resources\config\packages\cicada` 
* Added constant `CART_ADD_LINE_ITEM` in `Cicada\Core\Framework\RateLimiter\RateLimiter`.
* Added rate limiter `Cicada\Core\Framework\RateLimiter\Policy\SystemConfigLimiter` and policy type `system_config` to allow limitation configuration with `SystemConfigService`
* Added policy type `system_config` to `Cicada\Core\Framework\RateLimiter`
___
# API
* Added rate limitation for api route `store-api.checkout.cart.add`
