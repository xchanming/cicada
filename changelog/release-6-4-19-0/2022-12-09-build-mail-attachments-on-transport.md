---
title: Build mail attachments on transport
issue: NEXT-24474
author: d.neustadt
author_email: d.neustadt@cicada.com
author_github: dneustadt
---
# Core
* Added `Cicada\Core\Content\Mail\Service\MailAttachmentsConfig` for setting up attachments to be built before mail transport
* Added `Cicada\Core\Content\Mail\Service\MailAttachmentsBuilder` service for building media and document mail attachments
* Added `Cicada\Core\Content\Mail\Service\MailerTransportDecorator` to decorate `TransportInterface` and use `MailAttachmentsBuilder` to build attachments
* Added `Cicada\Core\Content\Mail\Service\Mail` as extension of `Symfony\Component\Mime\Email` to carry information regarding attachments in the form of `MailAttachmentsConfig`
