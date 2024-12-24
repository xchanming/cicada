---
title: Only send refresh index to existing es indices
issue: NEXT-12481
author: OliverSkroblin
author_email: o.skroblin@cicada.com 
author_github: OliverSkroblin
---
# Core
* Changed `\Cicada\Elasticsearch\Framework\Indexing\ElasticsearchIndexer`, the `refresh` request is now only send with existing indices names  
