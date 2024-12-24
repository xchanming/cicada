---
title: Use admin es config
issue: NEXT-22900
---

# Core

* Added new service `\Cicada\Elasticsearch\Product\ProductSearchQueryBuilder` to build an Elasticsearch query from the admin search configuration.
* Added some new fields to the `product` Elasticsearch index:
  * `manufacturerNumber`
  * `manufacturer.name`
  * `options.name`
  * `properties.name`
  * `categories.id`
  * `categories.name`
* Deprecated `\Cicada\Elasticsearch\Product\ElasticsearchProductDefinition::extendDocuments`, use `\Cicada\Elasticsearch\Product\ElasticsearchProductDefinition::fetch` instead
* Deprecated `fullText` and `fullTextBoosted` search fields in `product` Elasticsearch index, use ProductSearchQueryBuilder instead
