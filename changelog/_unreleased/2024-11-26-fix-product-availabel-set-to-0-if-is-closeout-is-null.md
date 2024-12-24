---
title: Fix an issue where the product available is set to 0 if the isCloseout is null
issue: NEXT-39546
author: Thuy Le
author_email: thuy.le@cicada.com
author_github: @thuylt
---
# Core
* Changed the SQL query to update the `product.available` column in `Cicada\Core\Content\Product\Stock\StockStorage` to avoid setting the value to 0 if the `is_closeout` column is null.
