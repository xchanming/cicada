---
title: Mark DemoData and StaticAnalysis namespaces as internal
issue: NEXT-23541
---
# Core
* Deprecated all classes in `Cicada\Core\DevOps\StaticAnalyze` and `Cicada\Core\DevOps\DemoData` namespaces, those classes will be internal in v6.5.0.0.
* Deprecated `\Cicada\Core\Migration\Traits\MigrationUntouchedDbTestTrait`, this trait will be removed, use `\Cicada\Core\Migration\Test\MigrationUntouchedDbTestTrait` instead.
