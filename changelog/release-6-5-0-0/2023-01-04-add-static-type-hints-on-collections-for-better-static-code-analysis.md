---
title: Add static as return value on EntityCollection methods for better static code analysis
author: Joshua Behrens
author_email: code@joshua-behrens.de
author_github: @JoshuaBehrens
issue: NEXT-25032
---
# Core
* Added PHPDoc @return type static to `\Cicada\Core\Framework\DataAbstractionLayer\EntityCollection::filterByProperty`, `\Cicada\Core\Framework\DataAbstractionLayer\EntityCollection::filterAndReduceByProperty` and `\Cicada\Core\Framework\DataAbstractionLayer\EntityCollection::getList` so it is understood as static instead of inherently mixed
