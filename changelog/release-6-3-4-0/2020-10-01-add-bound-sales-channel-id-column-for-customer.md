---
title: Add bound sales channel id column for customer
issue: NEXT-10973
---
# Core
*  Added a nullable `bound_sales_channel_id` foreign key into `customer` table.
*  Added `boundSalesChannel` ManyToOne association to `Cicada\Core\Checkout\Customer\CustomerDefinition`.
*  Added `boundCustomers` OneToMany association to `Cicada\Core\System\SalesChannel\SalesChannelDefinition`.
