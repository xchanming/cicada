---
title: Create handler for send mail action.
issue: NEXT-15154
---
# Core
* Added a new constant `SEND_MAIL` in `Cicada\Core\Content\Flow\Action\FlowAction`.
* Added `SendMailAction` class at `Cicada\Core\Content\Flow\Action\FlowAction` which used to send email to customers.
* Added `FlowSendMailActionEvent` class at `Cicada\Core\Content\Flow\Events\FlowSendMailActionEvent` which used to dispatch an event when `SendMailAction` is called.
* Added `MailAware` interface at `Cicada\Core\Framework\Event`.
* Deprecated `MailSendSubscriberBridgeEvent` at `Cicada\Core\Content\MailTemplate\Event\MailSendSubscriberBridgeEvent.php` use `FlowSendMailActionEvent` instead.
* Deprecated `MailSendSubscriber` at `Cicada\Core\Content\MailTemplate\Event\MailSendSubscriberBridgeEvent.php` use `SendMailAction` instead.
