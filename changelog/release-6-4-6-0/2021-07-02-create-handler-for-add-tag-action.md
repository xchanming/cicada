---
title: create-handler-for-add-tag-action
issue: NEXT-15155
---
# Core
* Added `AddCustomerTagAction` class at `Cicada\Core\Content\Flow\Action` which used to add a list of tags for customers.
* Added `AddOrderTagAction` class at `Cicada\Core\Content\Flow\Action` which used to add a list of tags for an order.
* Added `ADD_ORDER_TAG`, `ADD_CUSTOMER_TAG`, `REMOVE_ORDER_TAG` and `REMOVE_CUSTOMER_TAG` variables in `Cicada\Core\Content\Flow\Action\FlowAction`
* Remove `ADD_TAG` and `REMOVE_TAG` variables from `FlowAction` class at `Cicada\Core\Content\Flow\Action`.
* Remove `AddTagAction` class at `Cicada\Core\Content\Flow\Action`, use `AddCustomerTagAction` and `AddOrderTagAction` instead.
