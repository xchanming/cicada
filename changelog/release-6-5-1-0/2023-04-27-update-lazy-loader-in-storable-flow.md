---
title: Update lazy loader in Storable Flow
issue: NEXT-26184
---
# Core
* Added `lazyLoad` functions to replace deprecated `lazy` functions in:
  * `Cicada\Core\Content\Flow\Dispatching\StorerCustomerGroupStorer`
  * `Cicada\Core\Content\Flow\Dispatching\CustomerRecoveryStorer`
  * `Cicada\Core\Content\Flow\Dispatching\CustomerStorer`
  * `Cicada\Core\Content\Flow\Dispatching\NewsletterRecipientStorer`
  * `Cicada\Core\Content\Flow\Dispatching\OrderStorer`
  * `Cicada\Core\Content\Flow\Dispatching\OrderTransactionStorer`
  * `Cicada\Core\Content\Flow\Dispatching\ProductStorer`
  * `Cicada\Core\Content\Flow\Dispatching\UserStorer`
* Changed `lazy` method in `Cicada\Core\Content\Flow\Dispatching\StorableFlow` to correct the lazy loader.
