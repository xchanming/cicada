---
title: Added MailAware to OrderPaymentMethodChangedEvent
issue: NEXT-19674
author: PuetzD
author_github: PuetzD
---
# Core
* Added `Cicada\Core\Framework\Event\MailAware` to `Cicada\Core\Checkout\Order\Event\OrderPaymentMethodChangedEvent` to enable the FlowBuilder to send emails triggered by the OrderPaymentMethodChangedEvent
