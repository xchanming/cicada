---
title: Use AbstractTokenFilter in StopwordTokenFilterr
issue: NEXT-31263
---
# Core
* Changed dependency of `\Cicada\Elasticsearch\Product\StopwordTokenFilter` from `\Cicada\Core\Framework\DataAbstractionLayer\Search\Term\Filter\TokenFilter` to `\Cicada\Core\Framework\DataAbstractionLayer\Search\Term\Filter\AbstractTokenFilter` to allow token filter decorators works when adding elasticsearch bundle
