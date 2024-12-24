---
title: Add a separate Guzzle client for the First Run Wizard
issue: NEXT-22671
author: Frederik Schmitt
author_email: f.schmitt@cicada.com
author_github: fschmtt
---
# Core
* Changed the `Cicada\Core\Framework\Store\Services\StoreClientFactory` to accept optional middlewares for the `::create()` method instead of injecting them via constructor.
