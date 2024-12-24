---
title: Use initial order transaction state for processing payments
issue: NEXT-15779
author: Sebastian Seggewiss
author_email: s.seggewiss@cicada.com 
author_github: seggewiss
---
# Core
* Changed `\Cicada\Core\Checkout\Payment\Cart\PaymentTransactionChainProcessor::process` to use initial `OrderTransaction` state to process payments.
