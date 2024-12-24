---
title: Add since flag to Controllers and Entity Definitions
issue: NEXT-10572
---
# Core
* Added new `\Cicada\Core\Framework\Routing\Annotation\Since` annotation for controllers
* Added new method `since` to `\Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition`
* Added since annotation to all controllers
* Added since method to all entity definitions
* Changed `\Cicada\Core\Framework\Api\ApiDefinition\Generator\OpenApi\OpenApiDefinitionSchemaBuilder` to include information about "since" flag into OpenApi specification
