
---
title: Remove internal from ids collection
issue: NEXT-39183
author: Oliver Skroblin
author_email: oliver@goblin-coders.de
author_github: OliverSkroblin
---
# Core
* Removed @internal flag from `\Cicada\Core\Framework\Test\IdsCollection`
* Changed path from `\Cicada\Core\Framework\Test\IdsCollection` to `\Cicada\Core\Test\Stub\Framework\IdsCollection`
* Removed `\Cicada\Core\Framework\Test\TestDataCollection` which was extending IdsCollection and doing nothing than renaming
* Changed all tests consuming TestDataCollection, IdsCollection
