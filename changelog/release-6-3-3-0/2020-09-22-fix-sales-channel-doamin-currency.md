---
title: Fix sales channel doamin currency
issue: NEXT-7010
author_github: @Dominik28111
---
# Core
* Added new `$currencyId` parameter to `Cicada\Core\System\SalesChannel\Context\SalesChannelContextService` and `Cicada\Core\Profiling\Checkout\SalesChannelContextServiceProfiler` This parameter will be required with 6.4.0.0.
* Changed method `resolve()` and `handleSalesChannelContext()` in `Cicada\Core\Framework\Routing\SalesChannelRequestContextResolver` to pass the currency provided by the request attributes to the `SalesChannelContextService`.
