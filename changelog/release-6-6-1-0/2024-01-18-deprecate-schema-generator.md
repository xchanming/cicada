---
title: Deprecate CreateSchemaCommand and SchemaGenerator 
issue: NEXT-33257
author: Marcus Müller
author_email: 25648755+M-arcus@users.noreply.github.com
author_github: @M-arcus
---
# Core
* Deprecated `\Cicada\Core\Framework\DataAbstractionLayer\Command\CreateSchemaCommand` and `\Cicada\Core\Framework\DataAbstractionLayer\SchemaGenerator`, use `\Cicada\Core\Framework\DataAbstractionLayer\Command\CreateMigrationCommand` and `\Cicada\Core\Framework\DataAbstractionLayer\MigrationQueryGenerator` instead
___
# Next Major Version Changes

## \Cicada\Core\Framework\DataAbstractionLayer\Command\CreateSchemaCommand:
`\Cicada\Core\Framework\DataAbstractionLayer\Command\CreateSchemaCommand` will be removed. You can use `\Cicada\Core\Framework\DataAbstractionLayer\Command\CreateMigrationCommand` instead.

## \Cicada\Core\Framework\DataAbstractionLayer\SchemaGenerator:
`\Cicada\Core\Framework\DataAbstractionLayer\SchemaGenerator` will be removed. You can use `\Cicada\Core\Framework\DataAbstractionLayer\MigrationQueryGenerator` instead.
