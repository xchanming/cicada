---
title: Fix missing NotBlank constraint in EmailField
issue: NEXT-13348
---
# Core
* Changed function `getConstraints` in `Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\EmailFieldSerializer` to add `NotBlank` constraint.
