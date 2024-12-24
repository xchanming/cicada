---
title: Remove unnecessary cache hash creation for cache in the CurrencyFormatter and add cache reset
issue: NEXT-28971
author: Max
author_email: max@swk-web.com
author_github: @aragon999
---
# Core
* Removed unnecessary cache hash creation in the `Cicada\Core\System\Currency\CurrencyFormatter` for performance
* Added `reset` method to the `Cicada\Core\System\Currency\CurrencyFormatter` to reset the internal `NumberFormatter` cache on `kernel.reset`
