---
title: Cart duplicates when restored by token
issue: NEXT-38757
---
# Core
* Changed `Cicada\Core\System\SalesChannel\Context\CartRestorer::enrichCustomerContext` to not duplicate the cart when restoring by token.
