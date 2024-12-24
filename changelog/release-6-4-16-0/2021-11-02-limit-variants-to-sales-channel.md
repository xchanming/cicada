---
title: Limit variants to sales channel
issue: NEXT-11392
author_github: @Dominik28111
---
# Core
* Deprecated method `Cicada\Core\Content\Product\SalesChannel\Detail\AvailableCombinationLoader:load()` parameter `$salesChannelId` will be mandatory in `v6.5.0`.
* Changed method `Cicada\Core\Content\Product\SalesChannel\Detail\ProductConfiguratorLoader::load()` to hand over sales channel id to combination loader.
___
# Storefront
* Deprecated class `Cicada\Storefront\Page\Product\Configurator\AvailableCombinationLoader` use  `Cicada\Core\Content\Product\SalesChannel\Detail\AvailableCombinationLoader` instead.
