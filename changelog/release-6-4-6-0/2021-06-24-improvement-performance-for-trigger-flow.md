---
title: Improvement performance for trigger flow
issue: NEXT-15742
---
# Core
* Added `FlowIndexer`, `FlowIndexingMessage` and `FlowPayloadUpdater` class at `Cicada\Core\Content\Flow\DataAbstractionLayer`.
* Added `FlowIndexerEvent` class at `Cicada\Core\Content\Flow\Events`.
* Added `AbstractFlowLoader` interface and `FlowLoader` class at `Cicada\Core\Content\Flow`.
* Added `payload` column into table `flow`.
* Added `payload` property into `FlowEntity` and `FlowDefinition` class at `Cicada\Core\Content\Flow`.
* Added `FlowEvent` class at `Cicada\Core\Framework\Event`.
* Added `SequenceTree` and `SequenceTreeCollection` classes at `Cicada\Core\Content\Flow\SequenceTree`.
* Added `StopFlowAction` class at `Cicada\Core\Content\Flow\Action`.
