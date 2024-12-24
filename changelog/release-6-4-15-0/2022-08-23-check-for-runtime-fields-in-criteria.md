---
title: Check for Runtime fields in Criteria
issue: NEXT-22410
---
# Core
* Changed `\Cicada\Core\Framework\DataAbstractionLayer\Search\ApiCriteriaValidator` to check if a `Runtime` field was used in the criteria and throw an exception if that is the case.
* Changed `\Cicada\Core\Framework\Api\ApiDefinition\Generator\OpenApi\OpenApiDefinitionSchemaBuilder` to add a notice to all runtime fields, that they cannot be used inside a criteria.
