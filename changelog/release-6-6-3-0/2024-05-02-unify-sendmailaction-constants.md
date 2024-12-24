---
issue: NEXT-36018
title: Unify SendMailAction constants
author: Max
author_email: max@swk-web.com
author_github: @aragon999
---
# Core
* Deprecated constants `Cicada\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig::{ACTION_NAME,MAIL_CONFIG_EXTENSION}` use `Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction::{ACTION_NAME,MAIL_CONFIG_EXTENSION}` instead
* Deprecated constant `Cicada\Core\Content\MailTemplate\MailTemplateActions::MAIL_TEMPLATE_MAIL_SEND_ACTION` use `Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction::ACTION_NAME` instead
* Deprecated not needed class `Cicada\Core\Content\MailTemplate\MailTemplateActions`
___
# Next Major Version Changes
## Removal of deprecations
* Removed constants `Cicada\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig::{ACTION_NAME,MAIL_CONFIG_EXTENSION}` use `Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction::{ACTION_NAME,MAIL_CONFIG_EXTENSION}` instead
* Removed constant `Cicada\Core\Content\MailTemplate\MailTemplateActions::MAIL_TEMPLATE_MAIL_SEND_ACTION` use `Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction::ACTION_NAME` instead
* Removed class `Cicada\Core\Content\MailTemplate\MailTemplateActions` without replacement
