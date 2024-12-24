---
title: handle nullable app id
issue: NEXT-34131
author: Florian Keller
author_email: f.keller@cicada.com
---
# Core
* Changed the return value form `Cicada\Core\Framework\App\Aggregate\AppPaymentMethod\AppPaymentMethodEntity::getAppId()` to null|string.
