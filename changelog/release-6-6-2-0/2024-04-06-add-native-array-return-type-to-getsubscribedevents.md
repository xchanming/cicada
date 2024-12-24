---
issue: NEXT-34921
title: Add native array return type to getSubscribedEvents()
author: Max
author_email: max@swk-web.com
author_github: @aragon999
---
# Core
* Added native `array` return type to `Cicada\Core\System\UsageData\Subscriber\ShopIdChangedSubscriber::getSubscribedEvents()` and `Cicada\Core\System\UsageData\Consent\ConsentReporter::getSubscribedEvents()`
* Deprecated `TwigDateRequestListener` as it will become internal in v6.7.0
