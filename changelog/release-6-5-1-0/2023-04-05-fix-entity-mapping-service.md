---
title: Fix entity mapping service
issue: NEXT-26081
author: Sebastian Seggewiss
author_email: s.seggewiss@cicada.com
author_github: @seggewiss
---
# Administration
* Changed `entity-mapping.service.js` to use `Cicada.EntityDefinition.getDefinitionRegistry()` instead of `Entity.getDefiniton`
