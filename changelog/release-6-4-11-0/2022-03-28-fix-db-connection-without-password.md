---
title: Fix database connection without password.
issue: NEXT-20854
author: Daniel Sturm
author_github: dsturm
---
# Core
* Changed method `Cicada\Core\Maintenance\System\Struct\DatabaseConnectionInformation::asDsn()` to remove the check against the password to fix a connection error if no password is needed.
