---
title: Allow installation of apps in the FRW
issue: NEXT-26389
author: Frederik Schmitt
author_email: f.schmitt@cicada.com
author_github: fschmtt
---
# Core
* Changed `Cicada\Core\Framework\Store\Api\FirstRunWizardController` to pass installed apps to the `Cicada\Core\Framework\Store\Services\FirstRunWizardService`
* Changed `Cicada\Core\Framework\Store\Services\FirstRunWizardService` to additionally match recommendations against installed apps
* Changed `\Cicada\Core\Framework\Store\Struct\StorePluginStruct` to contain the extension's type
___
# Administration
* Changed `src/module/sw-first-run-wizard/component/sw-plugin-card/index.ts` to allow installation of apps
* Changed `src/module/sw-first-run-wizard/view/sw-first-run-wizard-plugins/sw-first-run-wizard-plugins.html.twig` to refresh recommendations after installing an extension

