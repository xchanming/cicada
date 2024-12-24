---
title: Fix cache control
issue: NEXT-20309
author: Soner Sayakci
author_email: s.sayakci@cicada.com
---

# Storefront
* Added `\Cicada\Storefront\Framework\Cache\CacheResponseSubscriber` to ensure `cache-control: private` is send to clients when the default PHP reverse proxy is enabled

