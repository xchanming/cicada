---
title: Add Elasticsearch explain mode
issue: NEXT-37103
---
# Core
* Changed 4th parameter $languageId of method `\Cicada\Elasticsearch\TokenQueryBuilder::build` from array to array|Context and renamed to $context
* Deprecated 4th parameter $context of method `\Cicada\Elasticsearch\TokenQueryBuilder::build` to only typed as Context from v6.7.0.0
* Added a new const `EXPLAIN_MODE` in `\Cicada\Elasticsearch\Framework\DataAbstractionLayer\ElasticsearchEntitySearcher`
* Changed method `\Cicada\Elasticsearch\Framework\DataAbstractionLayer\ElasticsearchEntitySearcher::search` to allow explain mode when `$context->hasState('EXPLAIN_MODE')`
* Deprecated `\Cicada\Elasticsearch\TokenQueryBuilder` as class will become `@final` from v6.7.0.0
