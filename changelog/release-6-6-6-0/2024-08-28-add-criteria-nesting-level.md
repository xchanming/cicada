---
title: Add criteria nesting level
issue: NEXT-38080
---

# Core

* Added `\Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria::getNestingLevel` method to get the nesting level of the criteria.
* Changed `\Cicada\Core\Content\Product\SalesChannel\SalesChannelProductDefinition::processCriteria` to add association only on root level.
