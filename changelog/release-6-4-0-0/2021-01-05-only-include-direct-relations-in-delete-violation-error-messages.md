---
title: Only include direct relations in delete violation error messages
issue: NEXT-13085
author: Tobias Berge
author_email: t.berge@cicada.com 
author_github: @tobiasberge
---
# Core
* Added new optional parameter `$restrictDeleteOnlyFirstLevel` in order to only include entities directly associated with the definition to the following methods:
    * `Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityForeignKeyResolver::getAffectedDeleteRestrictions`
    * `Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityForeignKeyResolver::fetch`
    * `Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityForeignKeyResolver::fetchAssociation`
