---
title: Handling incident rate-limit
issue: NEXT-29596
---
# Core
* Added new static methods `newsletterThrottled` into domain exception class `\Cicada\Core\Content\Newsletter\NewsletterException` for throttling.
* Changed method `subscribe` in `Cicada\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute` to try-catch `RateLimitExceededException`
