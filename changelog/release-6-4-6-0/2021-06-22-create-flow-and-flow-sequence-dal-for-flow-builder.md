---
title: Create flow and flow sequence DAL for flow builder
issue: NEXT-15110
---
# Core
* Added two new tables `flow` and `flow_sequence` to stored flow and flow sequence data for Flow Builder.
* Added entities, definition and collection for table `flow` at `Cicada\Core\Content\Flow`.
* Added entities, definition and collection for table `flow_sequence` at `Cicada\Core\Content\Flow\Aggregate\FlowSequence`.
* Added OneToMany association between `rule` and `flow_sequence`.
* Added new property `flowSequences` to `Cicada/Core/Content/Rule/RuleEntity`.
* Deprecated `EventActionRuleDefinition` at `Cicada\Core\Framework\Event\EventAction\Aggregate\EventActionRule`.
* Deprecated `EventActionSalesChannelDefinition` at `Cicada\Core\Framework\Event\EventAction\Aggregate\EventActionSalesChannel`.
* Deprecated `EventActionCollection`, `EventActionDefinition`, `EventActionEntity`, `EventActionEvents` and `EventActionSubscriber`, at `Cicada\Core\Framework\Event\EventAction`.
* Deprecated `eventActions` property in `RuleEntity` and `RuleDefinition` at `Cicada\Core\Content\Rule`.
* Deprecated `eventActions` property in `SalesChannelEntity` and `SalesChannelDefinition` at `Cicada\Core\System\SalesChannel`.
