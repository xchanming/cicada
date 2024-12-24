---
title: Check customer email valid when adding new order from order detail
issue: NEXT-24225
---
# API
* Changed `/api/_admin/check-customer-email-valid` from `Cicada\Administration\Controller\AdministrationController` to get `boundSalesChannelId` from request instead of `bound_sales_channel_Id`
* Changed `/api/_admin/check-customer-email-valid` from `Cicada\Administration\Controller\AdministrationController` to check `core.systemWideLoginRegistration.isCustomerBoundToSalesChannel` from system config before getting `boundSalesChannelId` from request
* Added service `SystemConfigService` into constructor of `Cicada\Administration\Controller\AdministrationController`
