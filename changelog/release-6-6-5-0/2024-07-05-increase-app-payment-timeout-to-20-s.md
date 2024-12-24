---
title: Increase app payment timeout to 20 s
issue: NEXT-31209
---
# Core
* Changed `\Cicada\Core\Framework\App\Payment\Payload\PaymentPayloadService` to override the request timeout for payment related requests to apps to 20s.

