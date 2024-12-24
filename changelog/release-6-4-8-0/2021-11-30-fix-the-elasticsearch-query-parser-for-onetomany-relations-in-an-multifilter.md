---
title: Fix the Elasticsearch Query Parser for OneToMany-Relations in an MultiFilter
issue: NEXT-17324
author: Simon Vorgers
author_email: s.vorgers@cicada.com
author_github: SimonVorgers
---
# Core
* Changed `Cicada\Elasticsearch\Framework\DataAbstractionLayer\CriteriaParser` to build an And-MultiFilter with OneToMany-Relations correctly.
