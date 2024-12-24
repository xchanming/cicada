---
title: Implement base code for trigger flow builder
issue: NEXT-15107
---
# Core
* Added `FlowExecutor` and `FlowState` classes at `Cicada\Core\Content\Flow`.
* Added `FlowDispatcher` class at `Cicada\Core\Content\Flow` to dispatch business event for Flow Builder.
* Added `AddOrderTagAction` class at `Cicada\Core\Content\Flow\Action`.
* Added `FlowAction` abstract class at `Cicada\Core\Content\Flow\Action`.
* Added `CustomerAware` and `OrderAware` interfaces at `Cicada\Core\Framework\Event`.
* Added function `getOrderId` into `Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent`.
* Deprecated `BusinessEventDispatcher` at `Cicada\Core\Framework\Event` which will be removed in v6.5.0.
* Added 'display_group' column into `flow_sequence` table.
* Added 'displayGroup' property into `FlowSequenceEntity` and `FlowSequenceDefinition` at `Cicada\Core\Content\Flow\Aggregate\FlowSequence`.
* Added `Sequence` class at `Cicada\Core\Content\Flow\SequenceTree`.
