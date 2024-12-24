---
title: Added a webhook validator to the app system
issue: NEXT-12223
author: Maike Sestendrup
---
# Core
* Added `Cicada\Core\Framework\Webhook\Hookable\HookableEventCollector`, to collect all hookable events and their required privileges.
* Added `Cicada\Core\Framework\Webhook\Hookable\HookableVadilator`, to validate the given webhooks and the related permissions in a `manifest.xml` file.
* Added `Cicada\Core\Framework\App\Manifest\ManifestValidator`, to validate a given `Cicada\Core\Framework\App\Manifest\Manifest`.
* Added the usage of `Cicada\Core\Framework\App\Manifest\ManifestValidator` in `Cicada\Core\Framework\App\Command\VerifyManifestCommand`.
* Changed the arguments for `Cicada\Core\Framework\App\Command\VerifyManifestCommand`. If no manifest file paths are specified, all `manifest.xml` files in the `development/custom/apps` directory are used.
