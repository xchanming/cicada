---
title: Add index to order table for turnover collector
issue: NEXT-23039
author: Frederik Schmitt
author_email: f.schmitt@cicada.com
author_github: fschmtt
---
# Core
* Added `Cicada\Core\Migration\V6_4\Migration1661759290AddDateAndCurrencyIndexToOrderTable` to add index `idx.order_date_currency_id` to the order table
