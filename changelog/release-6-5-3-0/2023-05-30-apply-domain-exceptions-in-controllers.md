---
title: Apply domain exceptions in controllers
issue: NEXT-27457
---
# Core
* Added new method `\Cicada\Core\Checkout\Cart\CartException::taxRuleNotFound`
* Added new methods `groupRequestNotFound`, `customersNotFound` in `\Cicada\Core\Checkout\Customer\CustomerException`
* Added new methods `promotionsNotFound`, `discountsNotFound` in `\Cicada\Core\Checkout\Promotion\PromotionException`
* Added various new methods to throw specific domain exception in `\Cicada\Core\Framework\Api\ApiException` and apply them in `\Cicada\Core\Framework\Api\` domain
* Added new domain exception class in `\Cicada\Core\Content\Category\CategoryException`
* Added new domain exception class in `\Cicada\Core\Content\Seo\SeoException`
___
# Elasticsearch
* Added new domain exception class in `\Cicada\Elasticsearch\Admin\ElasticsearchAdminException`
