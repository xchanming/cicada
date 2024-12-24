---
title: Update FlowExecutor to dispatch Webhook event
issue: NEXT-19012
---
# Core
* Added string field `url` into `Cicada\Core\Framework\App\Aggregate\FlowAction\AppFlowActionDefinition`
* Added property `url` into `Cicada\Core\Framework\App\Aggregate\FlowAction\AppFlowActionEntity`
* Added event `Cicada\Core\Framework\App\Event\AppFlowActionEvent`
* Added function `updateAppFlowActionWebhooks` into `Cicada\Core\Framework\App\Lifecycle\Persister\WebhookPersister`
* Added function `updateWebhooksFromArray` into `Cicada\Core\Framework\App\Lifecycle\Persister\WebhookPersister`
* Added exception `Cicada\Core\Framework\App\Exception\InvalidAppFlowActionVariableException`
* Added class `Cicada\Core\Framework\App\FlowAction\AppFlowActionProvider`
* Changed function `updateApp` in `Cicada\Core\Framework\App\Lifecycle\AppLifecycle` to update webhook when update app
* Changed function `getSubscribedEvents` in `Cicada\Core\Content\Flow\Indexing\FlowIndexer`.
* Added property `appFlowActionId` into `Cicada\Core\Content\Flow\Dispatching\Struct\ActionSequence`
* Added parameter `appFlowActionId` into method `Cicada\Core\Content\Flow\Dispatching\Struct\Sequence::createAction()`
* Changed method `executeAction` in `Cicada\Core\Content\Flow\Dispatching\FlowExecutor` to dispatcher correct event.
* Changed method `update` in `Cicada\Core\Content\Flow\Indexing\FlowPayloadUpdater` to add `app_flow_action_id` value to payload of flow.
