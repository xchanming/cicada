---
title: Order that contain inactive product can not be edited
issue: NEXT-15025
---
# Core
* Added new constant `KEEP_INACTIVE_PRODUCT` at `Cicada\Core\Content\Product\Cart\ProductCartProcessor`
* Changed function `enrich` to early return if product is inactive and context has permission `KEEP_INACTIVE_PRODUCT` at `Cicada\Core\Content\Product\Cart\ProductCartProcessor`
* Added new permission `KEEP_INACTIVE_PRODUCT` for constant `ADMIN_EDIT_ORDER_PERMISSIONS` at `Cicada\Core\Checkout\Cart\Order\OrderConverter`
