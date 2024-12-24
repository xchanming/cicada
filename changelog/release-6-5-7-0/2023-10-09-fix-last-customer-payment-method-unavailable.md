---
title: Fix last customer payment method unavailable
issue: NEXT-30753
author: Max Stegmeyer
author_email: m.stegmeyer@cicada.com
---
# Core
* Changed `\Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory` to use Sales Channel Payment Method if customers last payment method is not active or assigned.
