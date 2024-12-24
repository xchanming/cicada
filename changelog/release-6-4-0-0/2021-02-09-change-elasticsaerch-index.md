---
title: Change Elasticsearch index creation
issue: NEXT-12228
---
# Core
* Added Mapping property to `Cicada\Elasticsearch\Framework\Indexing\IndexCreator`
* Added `elasticsearch.index.mapping` parameter to di-container
* Changed `Cicada\Elasticsearch\Framework\Indexing\IndexCreator` to use `elasticsearch.index.mapping` instead of hardcoded mapping
