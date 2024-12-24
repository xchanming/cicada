---
title: Allow tokenizer decorators wihth elasticsearch package
author: Joshua Behrens
author_email: code@joshua-behrens.de
author_github: @JoshuaBehrens
issue: NEXT-31263
---
# Core
* Changed dependency of `\Cicada\Elasticsearch\Product\ProductSearchQueryBuilder` from `\Cicada\Core\Framework\DataAbstractionLayer\Search\Term\Tokenizer` to `\Cicada\Core\Framework\DataAbstractionLayer\Search\Term\TokenizerInterface` to allow tokenizer decorators work when adding elasticsearch bundle
