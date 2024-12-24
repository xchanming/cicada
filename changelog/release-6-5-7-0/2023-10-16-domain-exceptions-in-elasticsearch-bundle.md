---
title: Use domain exception in Elasticsearch bundle
issue: NEXT-31012 
---
# Core

* Added `\Cicada\Elasticsearch\ElasticsearchException` as factory class for all Elasticsearch exceptions.
* Deprecated `\Cicada\Elasticsearch\Exception\ElasticsearchIndexingException`, `\Cicada\Elasticsearch\Exception\NoIndexedDocumentsException`, `\Cicada\Elasticsearch\Exception\ServerNotAvailableException`, `\Cicada\Elasticsearch\Exception\UnsupportedElasticsearchDefinitionException` and `\Cicada\Elasticsearch\Exception\ElasticsearchIndexingException` use `\Cicada\Elasticsearch\ElasticsearchException` instead.
___
# Next Major Version Changes
## Removal of separate Elasticsearch exception classes
Removed the following exception classes:
* `\Cicada\Elasticsearch\Exception\ElasticsearchIndexingException`
* `\Cicada\Elasticsearch\Exception\NoIndexedDocumentsException`
* `\Cicada\Elasticsearch\Exception\ServerNotAvailableException`
* `\Cicada\Elasticsearch\Exception\UnsupportedElasticsearchDefinitionException`
* `\Cicada\Elasticsearch\Exception\ElasticsearchIndexingException`
Use the exception factory class `\Cicada\Elasticsearch\ElasticsearchException` instead.
