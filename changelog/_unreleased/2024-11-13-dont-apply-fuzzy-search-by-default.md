---
title: Dont apply fuzzy search by default
issue: NEXT-29543
---
# Core
* Changed method `\Cicada\Elasticsearch\TokenQueryBuilder::build` to not apply fuzzy search by if the field is not tokenized.
