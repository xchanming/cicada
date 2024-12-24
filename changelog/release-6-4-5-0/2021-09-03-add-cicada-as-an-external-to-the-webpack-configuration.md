---
title: Add Cicada as an external to the webpack configuration
issue: NEXT-16380
author: Jannis Leifeld
author_email: j.leifeld@cicada.com 
author_github: Jannis Leifeld
---
# Administration
* Added `Cicada` to the `externals` in the webpack configuration. This allows to import Cicada (e.g. `import { Module } from 'Cicada'`) instead of using the global Cicada object (e.g. `const { Module } = Cicada`). When plugins are using this they need to add `Cicada` to the "paths" in their `jsconfig.json` which redirect do `src/core/cicada`. It could also lead to an ESLint failure of `import/order` because the import have to placed before the local imports. Then you need to move the import to the top.
