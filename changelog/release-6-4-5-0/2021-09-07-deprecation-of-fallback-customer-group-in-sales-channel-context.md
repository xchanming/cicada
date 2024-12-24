---
title: Deprecation of fallback customer group in sales channel context
issue: NEXT-7602
---
# Core
* Deprecated method `getFallbackCustomerGroup` of SalesChannelContext, it will be removed in Cicada 6.5.0.0. Use `getCurrentCustomerGroup` instead.
* Deprecated constant `\Cicada\Core\Defaults::FALLBACK_CUSTOMER_GROUP`, it will be removed in Cicada 6.5.0.0. Use `getCurrentCustomerGroup` from `SalesChannelContext` instead.
* Deprecated constant `\Cicada\Core\Defaults::SALES_CHANNEL`, it will be removed in Cicada 6.5.0.0. No replacement exists, as any sales channel can be deleted

