---
title: Remain original Admin API sales channel source
issue: NEXT-39173
---
# Core
* Changed `Cicada\Core\Framework\Api\ControllerSalesChannelProxyController::setUpSalesChannelApiRequest` to use context admin source
* Added request attribute `ATTRIBUTE_CONTEXT_OBJECT` to the `\Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters` variable, which is passed to the `\Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface` in `\Cicada\Core\Framework\Routing\SalesChannelRequestContextResolver::resolve` method
