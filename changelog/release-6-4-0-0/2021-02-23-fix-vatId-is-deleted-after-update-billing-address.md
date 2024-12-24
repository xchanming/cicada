title: Fix the VAT number is deleted after update billing address
issue: NEXT-13685
---
# Core
* Removed support parameter `vatId`, use array `vatIds` instead for routes:
    * `Cicada\Core\Checkout\Customer\SalesChannel\UpsertAddressRoute:upsert`
    * `Cicada\Core\Checkout\Customer\SalesChannel\RegisterRoute:register`

