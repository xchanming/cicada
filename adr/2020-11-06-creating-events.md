---
title: Creating events in Cicada
date: 2020-11-06
area: core
tags: [event, context, sales-channel-context]
--- 

## Context

Events throughout Cicada are quite inconsistent.
It is not defined which data it must or can contain.
This mainly depends on the domain where the events are thrown.

## Decision

Developers should always have access to the right context of the current request,
at least the `Cicada\Core\Framework\Context` should be present as property in events.
If the event is thrown in a SalesChannel context,
the `Cicada\Core\System\SalesChannel\SalesChannelContext` should also be present as property.

## Consequences

From now on every new event must implement the `Cicada\Core\Framework\Event\CicadaEvent` interface.
If a `Cicada\Core\System\SalesChannel\SalesChannelContext` is also available,
the `Cicada\Core\Framework\Event\CicadaSalesChannelEvent` interface must be implemented instead.
