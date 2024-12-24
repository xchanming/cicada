---
title: Refactoring FlowEvent for implementing the flow DelayAction in Flow Builder
issue: NEXT-22263
---
# Core
* Added `StorableFlow` class in `Cicada\Core\Content\Flow\Dispatching` to implement the flow DelayAction in FlowBuilder.
* Changed the `dispatch`, `callFlowExecutor` methods in `Cicada\Core\Content\Flow\Dispatching\FlowDispatcher`, use the `StorableFlow` instead of the original events.
* Changed the `execute`, `executeSequence`, `executeIf`, `executeAction`, `executeSequence` methods in `Cicada\Core\Content\Flow\Dispatching\FlowExecutor`, use the `StorableFlow` instead of `FlowState` or `FlowEventAware`.
* Added new `FlowFactory` class in `Cicada\Core\Content\Flow\Dispatching` to create and restore the `StorableFlow`.
* Added new awareness interfaces:
  `Cicada\Core\Content\Flow\Dispatching\Aware\ConfirmUrlAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\ContactFormDataAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\ContentsAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\ContextTokenAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\CustomerRecoveryAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\DataAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\EmailAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\MessageAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\NameAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\NewsletterRecipientAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\OrderTransactionAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\RecipientsAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\ResetUrlAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\ShopNameAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\SubjectAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\TemplateDataAware`.
  `Cicada\Core\Content\Flow\Dispatching\Aware\UrlAware`.
* Added new classes storer to store the representation of available data and restore the available data for `StorableFlow` from the original events in  `Cicada\Core\Content\Flow\Dispatching\Storer`:
  `Cicada\Core\Content\Flow\Dispatching\Storer\ConfirmUrlStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\ContactFormDataStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\ContentsStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\ContextTokenStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\CustomerGroupStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\CustomerRecoveryStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\CustomerStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\DataStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\EmailStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\MessageStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\NameStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\NewsletterRecipientStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\OrderStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\OrderTransactionStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\RecipientsStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\ResetUrlStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\ShopNameStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\SubjectStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\TemplateDataStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\UrlStorer`.
  `Cicada\Core\Content\Flow\Dispatching\Storer\UserStorer`.
* Added index-key for the flow actions services tags.
* Changed all the flow builder actions in `Cicada\Core\Content\Flow\Dispatching\Action` from event subscriber to tagged services.
* Deprecated the `handle` functions in all the flow builder actions in `Cicada\Core\Content\Flow\Dispatching\Action`, use the function `handleFlow` instead.

___
# Next Major Version Changes
* In the next major, the flow actions are not executed over the symfony events anymore, we'll remove the dependence from `EventSubscriberInterface` in `Cicada\Core\Content\Flow\Dispatching\Action\FlowAction`.
that means, all the flow actions extends from `FlowAction` are become the services tag. 
* The flow builder will execute the actions via call directly the `handleFlow` function instead `dispatch` an action event.
* To get an action service in flow builder, we need define the tag action service with a unique key, that key should be an action name.
* About the data we'll use in the flow actions, the data will be store in the `StorableFlow $flow`, use `$flow->getStore('order_id')` or `$flow->getData('order')` instead of `$flowEvent->getOrder`.
  * Use `$flow->getStore($key)` if you want to get the data from aware interfaces. E.g: `order_id` in `OrderAware`, `customer_id` from `CustomerAware` and so on.
  * Use `$flow->getData($key)` if you want to get the data from original events or additional data. E.g: `order`, `customer`, `contactFormData` and so on.

**before**
```xml
 <service id="Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction">
    ...
    <tag name="flow.action"/>
</service>
```

```php
class FlowExecutor
{
    ...
    
    $this->dispatcher->dispatch($flowEvent, $actionname);
    
    ...
}

abstract class FlowAction implements EventSubscriberInterface
{
    ...
}

class SendMailAction extends FlowAction
{
    ...
    public static function getSubscribedEvents()
    {
        return ['action.name' => 'handle'];
    }
    
    public function handle(FlowEvent $event)
    {
        ...
        
        $orderId = $event->getOrderId();
        
        $contactFormData = $event->getConta();
        
        ...
    }
}
```

**after**
```xml
 <service id="Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction">
    ...
    <tag name="flow.action" key="action.mail.send" />
</service>
```

```php
class FlowExecutor
{
    ...
    
    $actionService = $actions[$actionName];
    
    $actionService->handleFlow($storableFlow);
    
    ...
}

abstract class FlowAction
{
    ...
}

class SendMailAction extends FlowAction
{
    ...
    // The `getSubscribedEvents` function has been removed.
    
    public function handleFlow(StorableFlow $flow)
    {
        ...
        
        $orderId = $flow->getStore('order_id');
        
        $contactFormData = $event->getData('contactFormData');
        
        ...
    }
}
```
