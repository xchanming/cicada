---
title: Fix Null Value Issue for 'zipcode' Column in Database
issue: NEXT-29921
---
# Core
* Changed `Cicada\Core\Checkout\Customer\Validation\AddressValidationFactory` to remove the `zipcode` definition in validation.
* Added migration `Cicada\Core\Migration\V6_5\Migration1695776504UpdateZipCodeOfTableOrderAddressToNullable` to update the `zipcode` column of the `order_address` table to `nullable`.
* Added migration `Cicada\Core\Migration\V6_5\Migration1695778183UpdateStreetOfTableCustomerAddressToNotNull` to update the `street` column of the `customer_address` table to `not null`.
___
# Storefront
* Changed `Cicada\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoader` to set the `zipcode` definition in validation.
