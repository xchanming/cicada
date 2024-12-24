---
title: Add a createCLIContext method to Context
issue: NEXT-30026
author: Jozsef Damokos
author_email: j.damokos@cicada.com
author_github: jozsefdamokos
---
# Core
* Added new method `createCLIContext` to the `Cicada\Core\Framework\Context` class. This can replace `createDefaultContext` (which is still internal) method in CLI context.
