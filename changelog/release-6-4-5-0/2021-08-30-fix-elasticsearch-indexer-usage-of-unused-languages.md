---
title: Fix Elasticsearch indexer usage of unused languages
issue: NEXT-16928
author: Sebastian Seggewiss
author_email: s.seggewiss@cicada.com 
author_github: seggewiss
---
# Core
* Added `\Cicada\Elasticsearch\Framework\Indexing\Event\ElasticsearchIndexerLanguageCriteriaEvent`
* Added filter to `\Cicada\Elasticsearch\Framework\Indexing\ElasticsearchIndexer::getLanguages`, to not use unused languages
* Added `\Cicada\Elasticsearch\Framework\Indexing\Event\ElasticsearchIndexerLanguageCriteriaEvent` dispatch to `\Cicada\Elasticsearch\Framework\Indexing\ElasticsearchIndexer::getLanguages`
