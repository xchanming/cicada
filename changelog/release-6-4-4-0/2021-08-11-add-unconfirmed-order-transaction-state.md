---
title: Add new "Unconfirmed" Order Transaction State
issue: NEXT-13601
author: Max Stegmeyer
author_email: m.stegmeyer@cicada.com
---
# Core
* Added new Order Transaction State `unconfirmed` to be used similar to `in_progress` but with enabled after order possibility.
* Added method `processUnconfirmed` to `Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler`
