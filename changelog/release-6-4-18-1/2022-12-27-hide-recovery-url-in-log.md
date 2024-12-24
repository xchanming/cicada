---
title: Hide recovery url in log
issue: NEXT-24679
---
# Core
* Added a new variable `%cicada.logger.exclude_events%` in `cicada.yaml`
* Added new log handler class `\\Cicada\Core\Framework\Log\Monolog\ExcludeFlowEventHandler` to exclude recovery password events and theirs according mail events from being logged if it's included in `%cicada.logger.exclude_events%` list
* Changed method `\Cicada\Core\Content\Flow\Dispatching\Action\handleFlow` to add flow's `eventName` into the `templateData` variable
* Changed class `\Cicada\Core\Content\MailTemplate\Service\Event\MailErrorEvent` to add the private property `eventName` in the constructor parameter
* Changed class `\Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent` to add the private property `eventName` in the constructor parameter
* Changed class `\Cicada\Core\Content\MailTemplate\Service\Event\MailSentEvent` to add the private property `eventName` in the constructor parameter
* Changed method `\Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent::getLogData` to add the `eventName` in log data
* Changed method `\Cicada\Core\Content\MailTemplate\Service\Event\MailErrorEvent::getLogData` to add the `eventName` in log data
* Changed method `\Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent::getLogData` to add the `eventName` in log data
* Changed method `\Cicada\Core\Content\MailTemplate\Service\Event\MailSentEvent::getLogData` to add the `eventName` in log data
* Added a new migration in `\Cicada\Core\Migration\V6_4\Migration1672164687FixTypoInUserRecoveryPasswordResetMail` to fix a typo in user recovery request mail template
* Changed `Cicada\Core\Content\Mail\Service\MailService` to inject `logger` into the service to log errors when they're thrown
