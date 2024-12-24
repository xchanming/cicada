---
title: Error after deleting a shipping method
issue: NEXT-13890
---
# Core
* Changed private function `getShippingMethod` in `Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory` to ensure always the default shipping method is as fallback available.
