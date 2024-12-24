---
title: Fix error redirect on account login
issue: NEXT-26995
author: Max Stegmeyer
author_email: m.stegmeyer@cicada.com
---

# Core
* Deprecated the constructor of the following exceptions, as there now is a domain exception in `Cicada\Core\Framework\Routing\RoutingException`
  * `Cicada\Core\Framework\Routing\Exception\MissingRequestParameterException`
  * `Cicada\Core\Framework\Routing\Exception\InvalidRequestParameterException`
