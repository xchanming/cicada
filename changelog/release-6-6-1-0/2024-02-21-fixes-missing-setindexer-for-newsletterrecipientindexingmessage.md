---
title: Fixes missing setIndexer for NewsletterRecipientIndexingMessage
issue: NEXT-33846
---
# Core
* Changed `\Cicada\Core\Content\Newsletter\Event\Subscriber\NewsletterRecipientDeletedSubscriber::onNewsletterRecipientDeleted` to set `newsletter_recipient.indexer` to the `NewsletterRecipientIndexingMessage`
