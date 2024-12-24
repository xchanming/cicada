---
title: Update AppLifecycle element for flow action
issue: NEXT-18951
---
# Core
* Added `FlowActionPersister` class in `Cicada\Core\Framework\App\Lifecycle\Persister` for saving data config from flow-action.xml to `app_flow_action` table.
* Added `getFlowActions` function in `Cicada\Core\Framework\App\Lifecycle\AppLoader` for getting data config from flow-action.xml.
* Added new property `appFlowAction` to `Cicada\Core\Content\Flow\Aggregate\FlowSequence\FlowSequenceEntity`.
* Added new property `flowSequences` to `Cicada\Core\Framework\App\Aggregate\FlowAction\AppFlowActionEntity`.
* Added OneToMany association between `app_flow_action` and `flow_sequence`.
* Changed `updateApp` function in `Cicada\Core\Framework\App\Lifecycle\AppLifecycle` for calling service `FlowActionPersister`.
* Changed `collect` function in `Cicada\Core\Content\Flow\Api\FlowActionCollector` to add more actions from app system.
