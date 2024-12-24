---
title: Fixed price serializer type checking
issue: NEXT-10738
---
# Core
* Changed `\Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\PriceFieldSerializer` to check the type of prices are `float` or `int`.
* Changed `\Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ListingPriceFieldSerializer` to transform prices formatted as `string` to `float`
* Changed `\Cicada\Core\Content\Product\DataAbstractionLayer\Indexing\ListingPriceUpdater` to transform prices formatted as `string` to `float`
