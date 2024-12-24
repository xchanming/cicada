---
title:              Exchange SwiftMailer with Symfony mailer
issue:              NEXT-12246
author:             Stefan Sluiter
author_email:       s.sluiter@cicada.com
author_github:      @ssltg
---
# Core
* Added `symfony/mailer ~4.4` to composer.json
* Added `Cicada\Core\Content\Mail\Service\MailSender`
* Added `Cicada\Core\Content\Mail\Service\MailService`
* Added `Cicada\Core\Content\Mail\Service\MailerTransportFactory`
* Added `Cicada\Core\Content\Mail\Service\AbstractMailSender`
* Added `Cicada\Core\Content\Mail\Service\AbstractMailService`
* Added `Cicada\Core\Framework\Feature\Exception\FeatureActiveException`
* Added argument `emailService` with type `Cicada\Core\Content\Mail\Service\AbstractMailService` in `Cicada\Core\Content\MailTemplate\Subscriber\MailSendSubscriber`

* Changed argument type of argument `$message` in `Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent` from `Swift_Message` to `Symfony\Component\Mime\Email`
* Changed return type of method `getMessage` in `Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent` from `Swift_Message` to `Symfony\Component\Mime\Email`
* Changed `Cicada\Core\Framework\Feature\FeatureNotActiveException` to `Cicada\Core\Framework\Feature\Exception\FeatureNotActiveException`

* Removed `Cicada\Core\Content\MailTemplate\Service\MailSender`
* Removed `Cicada\Core\Content\MailTemplate\Service\MailSenderInterface`
* Removed `Cicada\Core\Content\MailTemplate\Service\MailService`
* Removed `Cicada\Core\Content\MailTemplate\Service\MailServiceInterface`
* Removed `Cicada\Core\Content\MailTemplate\Service\MessageFactoryInterface`
* Removed `Cicada\Core\Content\MailTemplate\Service\MessageFactory`
* Removed `Cicada\Core\Content\MailTemplate\Service\MessageTransportFactoryInterface`
* Removed `Cicada\Core\Content\MailTemplate\Service\MessageTransportFactory`
* Removed `Cicada\Core\Content\MailTemplate\Service\MailerTransportFactory`
* Removed `Cicada\Core\Content\MailTemplate\Service\MailerTransportFactoryInterface`
* Removed argument `mailService` in `Cicada\Core\Content\MailTemplate\Subscriber\MailSendSubscriber`
* Removed method `createMessage` in `Cicada\Core\Content\MailTemplate\Service\MessageFactory` use `createMail` instead
___
# Administration
* Removed block `sw_settings_mailer_smtp_authentication`
* Removed method `authenticationOptions` in component `sw-settings-mailer-smtp`

