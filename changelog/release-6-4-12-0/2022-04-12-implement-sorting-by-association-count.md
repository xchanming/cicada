---
title: Implement sorting by association count
issue: NEXT-21006
author: d.neustadt
author_email: d.neustadt@cicada.com
author_github: dneustadt
---
# Core
* Added `Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\CountSorting`
* Changed `Cicada\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder::parseSorting` to read value of key `type` in iteration of the `sort` param and instanciate `CountSorting` if `type` equals `count`
* Changed `Cicada\Core\Framework\DataAbstractionLayer\Dbal\CriteriaQueryBuilder::addSortings` to handle instances of `CountSorting`
* Added `Cicada\Elasticsearch\Sort\CountSort` as extension of `FieldSort`
* Changed `Cicada\Elasticsearch\Framework\DataAbstractionLayer\CriteriaParser::parseSorting` to return instance of `CountSort` if instance of `CountSorting` is given
