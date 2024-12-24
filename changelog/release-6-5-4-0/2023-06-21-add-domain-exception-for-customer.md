---
title: Add Domain Exception for customer
issue: NEXT-26919
---
# Core
* Added new static methods for domain exception class `Cicada\Core\Checkout\Customer\CustomerException`.
* Added new static method `addressNotFound` for domain exception class `Cicada\Core\Checkout\Cart\CartException`.
* Added new static method `customerAuthThrottledException` for domain exception class `Cicada\Core\Checkout\Order\OrderException`.
* Added new static method `customerNotFoundByIdException` for domain exception class `Cicada\Core\System\SalesChannel\SalesChannelException`.
* Deprecated the following exceptions in replacement for Domain Exceptions:
    * `Cicada\Core\Checkout\Customer\Exception\CannotDeleteActiveAddressException`
    * `Cicada\Core\Checkout\Customer\Exception\CustomerGroupRegistrationConfigurationNotFound`
    * `Cicada\Core\Checkout\Customer\Exception\CustomerWishlistNotActivatedException`
    * `Cicada\Core\Checkout\Customer\Exception\InactiveCustomerException`
    * `Cicada\Core\Checkout\Customer\Exception\LegacyPasswordEncoderNotFoundException`
    * `Cicada\Core\Checkout\Customer\Exception\WishlistProductNotFoundException`
    * `Cicada\Core\Checkout\Customer\Exception\NoHashProvidedException`
