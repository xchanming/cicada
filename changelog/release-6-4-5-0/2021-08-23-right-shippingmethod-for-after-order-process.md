---
title: Right ShippingMethod for after order process
issue: NEXT-16622
author: Sebastian Seggewiss
author_email: s.seggewiss@cicada.com 
author_github: seggewiss
---
# Storefront
* Changed `\Cicada\Storefront\Controller\AccountOrderController::editOrder` to use the shipping method of the most recent order delivery
