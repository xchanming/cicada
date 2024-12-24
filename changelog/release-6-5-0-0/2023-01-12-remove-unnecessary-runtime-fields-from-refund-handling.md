---
title: Remove unnecessary runtime fields from refund handling
issue: NEXT-24813
author: Lennart Tinkloh
author_email: l.tinkloh@cicada.com
author_github: @lernhart
---
# Core
* Removed `totalAmount` runtime field from `\Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCapture\OrderTransactionCaptureDefinition` and its corresponding entity, as the field was unused.
* Removed `totalAmount` runtime field from `\Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundDefinition` and its corresponding entity, as the field was unused.
* Removed `refundPrice` runtime field from `\Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefundPosition\OrderTransactionCaptureRefundPositionDefinition` and its corresponding entity, as the field was unused.
