---
title: Deprecate Sales Channel API
issue: NEXT-10706
---
# Core

* Deprecated following classes :
    * `Cicada\Core\Checkout\Cart\SalesChannel\SalesChannelChartController`
    * `Cicada\Core\Checkout\Cart\SalesChannel\SalesChannelCheckoutController`
    * `Cicada\Core\Checkout\Customer\SalesChannel\SalesChannelCustomerController`
    * `Cicada\Core\Content\Cms\SalesChannel\SalesChannelCmsPageController`
    * `Cicada\Core\Content\Newsletter\SalesChannel\SalesChannelNewsletterController`
    * `Cicada\Core\Content\Product\SalesChannel\CrossSelling\SalesChannelCrossSellingController`
    * `Cicada\Core\Framework\Api\Response\Type\SalesChannel\JsonApiType`
    * `Cicada\Core\Framework\Api\Response\Type\SalesChannel\JsonType`
    * `Cicada\Core\Framework\Routing\SalesChannelApiRouteScope`
    * `Cicada\Core\System\SalesChannel\Entity\SalesChannelApiController`
    * `Cicada\Core\System\SalesChannel\SalesChannel\SalesChannelApiSchemaController`
    * `Cicada\Core\System\SalesChannel\SalesChannel\SalesChannelContextController`

___
# API

* Deprecated Sales Channel API will be removed with 6.4.0.
    * Use the replacements routes from the Store-API 

___

# Upgrade Information

## Deprecation of the Sales Channel API

As we finished with the implementation of our new Store API, we are deprecating the old Sales Channel API. 
The removal is planned for the 6.4.0.0 release. Projects are using the current Sales Channel API can migrate on api route base. 
