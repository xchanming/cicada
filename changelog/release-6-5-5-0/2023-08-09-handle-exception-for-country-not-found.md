---
title: Handle exception for country not found
issue: NEXT-29735
---
# Core
* Added new exception method `countryNotFound` for `Cicada\Core\Checkout\Customer\CustomerException`
* Added an alternative exception by throwing `CustomerException::countryNotFound()` in `register` method of `Cicada\Core\Checkout\Customer\SalesChannel\RegisterRoute`
* Added an alternative exception by throwing `CustomerException::countryNotFound()` in `validate` method of `Cicada\Core\Checkout\Customer\Validation\Constraint\CustomerZipCodeValidator`
* Added new domain exception `Cicada\Core\System\Country\CountryException`
