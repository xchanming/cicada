---
title: Improve aggregation name validation
issue: NEXT-37397
---

# Core

* Changed `\Cicada\Core\Framework\DataAbstractionLayer\Search\Parser\AggregationParser` to validate that the aggregation name does not contain question marks or colon,
