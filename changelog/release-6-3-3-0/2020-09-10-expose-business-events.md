---
title:              expose business events
issue:              NEXT-10701
author:             Oliver Skroblin
author_email:       o.skroblin@cicada.com
author_github:      @OliverSkroblin
---
# Core
* Added remaining events to `\Cicada\Core\Framework\Event\BusinessEvents`
* Added `\Cicada\Core\Checkout\Cart\RuleLoader` to load all routes
* Added validation in `\Cicada\Core\Checkout\Cart\Order\OrderConverter::assembleSalesChannelContext` to make sure that all data is available 
* Added `\Cicada\Core\Framework\Event\BusinessEventCollector`, which returns a collection of all business events 
* Added `\Cicada\Core\Framework\Event\BusinessEventCollectorEvent`, which allows to mutate business events
* Added `\Cicada\Core\Framework\Event\BusinessEventCollectorResponse`, which returned by the collector
* Added `\Cicada\Core\Framework\Event\BusinessEventDefinition`, which contains all information about a business event                                        
* Deprecated `\Cicada\Core\Framework\Event\BusinessEventRegistry::getEvents` use `\Cicada\Core\Framework\Event\BusinessEventCollector::collect` instead 
* Deprecated `\Cicada\Core\Framework\Event\BusinessEventRegistry::getEventNames` use `\Cicada\Core\Framework\Event\BusinessEventCollector::collect` instead
* Deprecated `\Cicada\Core\Framework\Event\BusinessEventRegistry::getAvailableDataByEvent` use `\Cicada\Core\Framework\Event\BusinessEventCollector::collect` instead 
* Deprecated `\Cicada\Core\Framework\Event\BusinessEventRegistry::add` use `\Cicada\Core\Framework\Event\BusinessEventRegistry::addClasses` instead
* Deprecated `\Cicada\Core\Framework\Event\BusinessEventRegistry::addMultiple` use `\Cicada\Core\Framework\Event\BusinessEventRegistry::addClasses` instead
* Added `\Cicada\Core\Checkout\Order\Event\OrderStateChangeCriteriaEvent`, which allows to load additional data for order mails
___
# API
* Added `api.info.business-events` route
* Deprecated `api.info.events` use `api.info.business-events` instead
