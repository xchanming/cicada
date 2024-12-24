---
title: Disable extensions per env variable
issue: NEXT-20852
---
# Core 
* Changed entry files `index.php` and `cicada.php` to use `ComposerPluginLoader` if env variable `DISABLE_EXTENSIONS` is set to true.
* Added `\Cicada\Core\Framework\App\EmptyActiveAppsLoader`, that will be used if env variable `DISABLE_EXTENSIONS` is set to true.
* Changed `\Cicada\Core\Framework\Adapter\Twig\EntityTemplateLoader`, `\Cicada\Core\Framework\Script\Execution\ScriptExecutor` and `\Cicada\Core\Framework\Webhook\WebhookDispatcher` to do an early return if env variable `DISABLE_EXTENSIONS` is set to true.
___
# Upgrade information
## Disabling of custom extensions with .env variable

In cluster setups you can't dynamically install or update extensions, because those changes need to be done on every host server.
Therfore such operations should be performed during a deployment/rollout and not dynamically.

For this you now can set the variable `DISABLE_EXTENSIONS=1` in your `.env` file.
This will:
* Only load plugins that are installed over composer, all other plugins are ignored.
* Ignore all apps that may be installed.

Another advantage of that flag is that it reduces the amount of database queries cicada needs to perform on each request, and thus making cicada faster and reducing the load on the database. 
