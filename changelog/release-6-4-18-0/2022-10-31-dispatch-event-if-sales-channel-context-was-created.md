---
title: Dispatch event if sales channel context was created
issue: NEXT-23355
author: Martin Krzykawski
author_email: m.krzykawski@cicada.com
---
# Core
* Added `Cicada\Core\System\SalesChannel\Event\SalesChannelContextCreatedEvent` that will be dispatched in `Cicada\Core\System\SalesChannel\Context\SalesChannelContextService::get` if a `Cicada\Core\System\SalesChannel\SalesChannelContext` was created.
