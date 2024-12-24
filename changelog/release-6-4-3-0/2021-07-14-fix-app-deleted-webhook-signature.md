---
title: Fix AppDeletedEvent Webhook signature
issue: NEXT-16175
---
# Core
* Added `$secret` property to `\Cicada\Core\Framework\Webhook\Message\WebhookEventMessage`
* Changed `\Cicada\Core\Framework\Webhook\WebhookDispatcher` to pass the app secret to the `WebhookEventMessage`, thus preventing that the webhook can not be signed, when an app is deleted before the webhook is dispatched.
* Changed `\Cicada\Core\Framework\Webhook\Handler\WebhookEventMessageHandler` to use the secret from the `WebhookEventMessage`, instead of re-fetching it from the DB, to sign webhooks.
