---
title: Handle form validation on the modal
issue: NEXT-27716
---
# Storefront
* Changed method `addressBook` at `Cicada\Storefront\Controller\AddressController` to load the `AddressPageListingLoader` at the top, to ensure it will always be rendered in Twig template.
