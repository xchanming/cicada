---
title: Add system config webhook
issue: NEXT-26080
---

# Core

* Added new webhook `app.config.changed` to react as app for system config changes.

___

# Next Major Version Changes

* Changed the following classes to be internal:
  - `\Cicada\Core\Framework\Webhook\Hookable\HookableBusinessEvent`
  - `\Cicada\Core\Framework\Webhook\Hookable\HookableEntityWrittenEvent`
  - `\Cicada\Core\Framework\Webhook\Hookable\HookableEventFactory`
  - `\Cicada\Core\Framework\Webhook\Hookable\WriteResultMerger`
  - `\Cicada\Core\Framework\Webhook\Message\WebhookEventMessage`
  - `\Cicada\Core\Framework\Webhook\ScheduledTask\CleanupWebhookEventLogTask`
  - `\Cicada\Core\Framework\Webhook\BusinessEventEncoder`
  - `\Cicada\Core\Framework\Webhook\WebhookDispatcher`
