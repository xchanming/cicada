---
title: Add newsletter.unsubscribe Event
issue: NEXT-14540
---
# Core
* Added `\Cicada\Core\Content\Newsletter\Event\NewsletterUnsubscribeEvent`
* Changed `\Cicada\Core\Content\Newsletter\SalesChannel\NewsletterUnsubscribeRoute` to dispatch `NewsletterUnsubscribeEvent`
* Deprecated `\Cicada\Core\Content\Newsletter\Event\NewsletterUpdateEvent` it will be removed in 6.5.0.0 as it was never thrown.
