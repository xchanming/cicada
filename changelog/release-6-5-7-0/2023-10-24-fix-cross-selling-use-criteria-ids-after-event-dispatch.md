---
title: Fix custom field rule with multi select
issue: NEXT-31275
author: Jan Emig
author_email: j.emig@one-dot.de
author_github: @Xnaff
---
# Core
* Changed method `loadByIds` in `Cicada\Core\Content\Product\SalesChannel\CrossSelling\ProductCrossSellingRoute` to use the ids from the context after the event dispatch. 
