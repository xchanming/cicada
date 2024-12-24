---
title: Reset the SalesChannelContext when it is expired
issue: NEXT-20079
---
# Core
* Changed `\Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister::load()` to reset the context when it is expired.
