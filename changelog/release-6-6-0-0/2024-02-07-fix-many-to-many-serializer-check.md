---
title: Fix many to many serializer check
issue: NEXT-33362
---
# Core
* Changed the data structure check to be in the correct place in `\Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ManyToManyAssociationFieldSerializer`
* Deprecated `\Cicada\Core\Framework\DataAbstractionLayer\Exception\DecodeByHydratorException` use `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException::decodeHandledByHydrator` instead
* Changed the various association field serializers to throw `DataAbstractionLayerException`'s instead of `\RuntimeException`'s
