---
title: Fix bug where double slash would break salesChannelRequired check
issue: NEXT-28214
---
# Core
* Changed `\Cicada\Storefront\Framework\Routing\RequestTransformer::isSalesChannelRequired` to trim leading slashes which fixes a bug when calling the `//admin` route  
