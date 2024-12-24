---
title: Load SalesChannelAnalytics for storefront sales channels
issue: NEXT-32097
author: Benedikt Schulze Baek
author_email: b.schulze-baek@cicada.com
author_github: bschulzebaek
---
# Core
* Added `Cicada\Core\System\SalesChannel\Subscriber\SalesChannelAnalyticsLoader` to load the SalesChannelAnalytics configuration for storefront sales channels during the `StorefrontRenderEvent`
