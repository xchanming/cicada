---
title: Add timezone to DateHistogram
issue: NEXT-15752
---
# Core
* Added timezone parameter to `\Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation`
___
# API
* The aggregation `histogram` supports now `timeZone` parameter to determine dates in that timezone
