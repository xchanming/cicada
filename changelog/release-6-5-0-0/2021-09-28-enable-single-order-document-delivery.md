---
title: Enable single order document delivery
issue: NEXT-16681
author: Sebastian Seggewiss
author_email: s.seggewiss@cicada.com 
author_github: seggewiss
---
# Core
* Deprecated `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_STORNO`
* Added `\Cicada\Core\Content\MailTemplate\Service\AbstractAttachmentService`
* Added `\Cicada\Core\Content\MailTemplate\Service\AttachmentService`
* Added MailTemplateType `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_INVOICE`
* Added MailTemplateType `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_DELIVERY_NOTE`
* Added MailTemplateType `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_CREDIT_NOTE`
* Added MailTemplateType `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_CANCELLATION_INVOICE`
* Added MailTemplate for MailTemplateType `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_INVOICE`
* Added MailTemplate for MailTemplateType `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_DELIVERY_NOTE`
* Added MailTemplate for MailTemplateType `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_CREDIT_NOTE`
* Added MailTemplate for MailTemplateType `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_CANCELLATION_INVOICE`
___
# API
* Added parameter `documentIds` to route `api.action.mail_template.send`
___
# Administration
* Added `sendMailTemplate` function to `mail.api.service`
___
# Upgrade Information
## Core
* Replace `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_STORNO` with `\Cicada\Core\Content\MailTemplate\MailTemplateTypes::MAILTYPE_DOCUMENT_CANCELLATION_INVOICE`.
