---
title: Remove Migration deprecations
issue: NEXT-21203
---
# Core
* Changed all `MigrationSteps` to be `@internal`
* Removed all Migrations in old migration namespaces, all migrations are now in a namespace specifying the major version, where they were added.
* Deprecated `\Cicada\Core\Framework\Migration\MigrationSource::addReplacementPattern()` it will be removed in v6.6.0.0 as it is not used anymore.
* Removed `\Cicada\Core\Migration\Traits\MigrationUntouchedDbTestTrait` use `\Cicada\Core\Migration\Test\MigrationUntouchedDbTestTrait` instead.
* Removed deprecated class `\Cicada\Core\Framework\Migration\Api\MigrationController`.
* Removed deprecated methods in `\Cicada\Core\Framework\Migration\MigrationStep`.
