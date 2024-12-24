---
title: Wrap StorefrontController render call in CicadaException
issue: NEXT-27284
---
# Storefront
* Added `StorefrontException` class in `Cicada\Storefront\Controller\Exception`.
* Changed `renderView` method in `Cicada\Storefront\Controller\StorefrontController` to wrap render view in domain exception.
