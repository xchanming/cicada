---
title: Allow update product description containing only invalid tag
issue: NEXT-38643
---
# Core
* Changed the `encode` function in `Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\LongTextFieldSerializer` to set null to value of data again if it's empty after sanitize. 
