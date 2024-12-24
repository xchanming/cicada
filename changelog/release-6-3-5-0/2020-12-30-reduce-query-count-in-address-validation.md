---
title: Reduce query count in address validation
issue: NEXT-12346
author: OliverSkroblin
author_email: o.skroblin@cicada.com 
author_github: OliverSkroblin
---
# Core
* Added internal cache for country availability query in `\Cicada\Core\Checkout\Cart\Address\AddressValidator`
* Changed customer address validation to be executed only on the checkout confirm page
