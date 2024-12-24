---
title: Custom field helper
issue: NEXT-25977
author: Oliver Skroblin
author_email: o.skroblin@cicada.com
---
# Core
* Added helper functions to access and change custom fields in entities:
  * `\Cicada\Core\Framework\DataAbstractionLayer\EntityCollection::setCustomFields`
  * `\Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait::changeCustomFields`
  * `\Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait::getCustomFieldValues`
