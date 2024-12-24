---
title: Fix salutation auto set default when create customer in admin
issue: NEXT-25264
---
# Administration
* Added computed `defaultSalutationId`, `salutationCriteria`, `salutationRepository` in to get default salutation
  * `sw-customer-create` component.
  * `sw-customer-detail` component.
  * `sw-order-new-customer-modal` component.
  * `sw-customer-detail-addresses` component.
* Changed `createdComponent` method in to set default salutation for customer
  * `sw-customer-create` component.
  * `sw-customer-detail` component.
  * `sw-order-new-customer-modal` component.
___
# Core
* Changed `Cicada\Core\Checkout\Customer\SalesChannel\ChangeCustomerProfileRoute::change` to set default to `salutationId`
* Changed `Cicada\Core\Checkout\Customer\SalesChannel\RegisterRoute::register` to set default to `salutationId`
* Changed `Cicada\Core\Checkout\Customer\SalesChannel\UpsertAddressRoute::upsert` to set default to `salutationId`
* Changed `Cicada\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressDefinition` to remove flag required with salutationId.
* Added `Cicada\Core\System\Salutation\SalutationSorter` to sort salutations
* Added subscribers to set salutation with default not specified
  * `Cicada\Core\Checkout\Order\Subscriber\OrderSalutationSubscriber`
  * `Cicada\Core\Checkout\Customer\Subscriber\CustomerSalutationSubscriber`
  * `Cicada\Core\Content\Newsletter\Subscriber\NewsletterRecipientSalutationSubscriber`
___
# Storefront
* Changed function `register` in `Cicada\Storefront\Controller\RegisterController` to remove `definition` `salutationId`.
* Changed `Cicada\Storefront\Page\Account\Login\AccountLoginPageLoader::load` to sort salutations by `salutation_key` not specified.
* Changed `Cicada\Storefront\Page\Account\Profile\AccountProfilePageLoader::load` to sort salutations by `salutation_key` not specified.
* Changed `Cicada\Storefront\Page\Address\Detail\AddressDetailPageLoader::load` to sort salutations by `salutation_key` not specified.
* Changed `storefront/component/address/address-personal.html.twig` to remove attribute `required` with `salutationId`.
