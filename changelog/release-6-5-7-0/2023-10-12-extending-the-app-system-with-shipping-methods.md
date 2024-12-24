---
title: Extending the app system with shipping methods
issue: NEXT-30229
---

# Core

+ Added the possibility to add new shipping methods via app manifest
* Added new entity `app_shipping_method` in `Cicada\Core\Framework/App/Aggregate/AppShippingMethod/AppShippingMethodDefinition`
* Added following new classes
    * `Cicada\Core\Framework\App\Lifecycle\Persister\ShippingMethodPersister`
    * `Cicada\Core\Framework\App\Manifest\Xml\ShippingMethod\ShippingMethods`
    * `Cicada\Core\Framework\App\Manifest\Xml\ShippingMethod\ShippingMethod`
    * `Cicada\Core\Framework\App\Manifest\Xml\ShippingMethod\DeliveryTime`
