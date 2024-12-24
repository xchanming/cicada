---
title: Fix stream builder
issue: NEXT-10946
author: Oliver Skroblin
author_email: o.skroblin@cicada.com 
author_github: Oliver Skroblin
---
# Core
* Changed `\Cicada\Core\Content\ProductStream\Service\ProductStreamBuilder`, the class uses now the generated `product_stream.api_filter` column to build the filters
* Changed `\Cicada\Core\Content\ProductStream\DataAbstractionLayer\ProductStreamIndexer`, the class now considers the `position` field to generate the `api_filter` value
* Added generic `\Cicada\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException`
