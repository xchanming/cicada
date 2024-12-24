---
title: make the FirstRunWizardClient depend less on StoreService
issue: NEXT-18846
author: Adrian Les
author_email: a.les@cicada.com
author_github: adrianles
---
# Core
* Added `Cicada\Core\Framework\Store\Services\TrackingEventClient`
* Changed `Cicada\Core\Framework\Store\Services\FirstRunWizardClient` to use `Cicada\Core\Framework\Store\Services\TrackingEventClient`
* Deprecated `Cicada\Core\Framework\Store\Services\StoreService::fireTrackingEvent()`
* Deprecated `Cicada\Core\Framework\Store\Services\StoreService::getLanguageByContext()`
