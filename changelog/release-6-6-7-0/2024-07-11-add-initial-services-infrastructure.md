---
title: Add initial services infrastructure
issue: NEXT-34133
author: Aydin Hassan
author_email: a.hassan@cicada.com
author_github: Aydin Hassan
---
# Core
* Added new core services module `src/Core/Services`, which is all internal and not public API for the time being.
* Added command and scheduled task to install new services `\Cicada\Core\Services\Command\Install` & `\Cicada\Core\Services\ScheduledTask\InstallServicesTask`. Service registry URL is not configured so no services will be installed by default.
* Added new `\Cicada\Core\Services\ServiceSourceResolver` to manage service apps.
* Added new `selfManaged` column to the app entity which can be used to hide services from the administration and prevent manual updates.
___
# API
* Added new `/api/services/trigger-update` endpoint which allows services to trigger cicada checking for a service update
