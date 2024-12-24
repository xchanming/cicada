---
title: Unify setup scripts
issue: NEXT-17218
---
# Core
* Added `\Cicada\Core\Maintenance\Maintenance` bundle
  * Added `\Cicada\Core\Maintenance\System\Command\SystemGenerateAppSecretCommand`
  * Deprecated `\Cicada\Core\DevOps\System\Command\SystemGenerateAppSecretCommand`, use `\Cicada\Core\Maintenance\System\Command\SystemGenerateAppSecretCommand` instead
  * Added `\Cicada\Core\Maintenance\System\Command\SystemGenerateJwtSecretCommand`
  * Deprecated `\Cicada\Core\DevOps\System\Command\SystemGenerateJwtSecretCommand`, use `\Cicada\Core\Maintenance\System\Command\SystemGenerateJwtSecretCommand` instead
  * Added `\Cicada\Core\Maintenance\System\Command\SystemInstallCommand`
  * Deprecated `\Cicada\Core\DevOps\System\Command\SystemInstallCommand`, use `\Cicada\Core\Maintenance\System\Command\SystemInstallCommand` instead
  * Added `\Cicada\Core\Maintenance\System\Command\SystemSetupCommand`
  * Deprecated `\Cicada\Core\DevOps\System\Command\SystemSetupCommand`, use `\Cicada\Core\Maintenance\System\Command\SystemSetupCommand` instead
  * Added `\Cicada\Core\Maintenance\System\Command\SystemUpdateFinishCommand`
  * Deprecated `\Cicada\Core\DevOps\System\Command\SystemUpdateFinishCommand`, use `\Cicada\Core\Maintenance\System\Command\SystemUpdateFinishCommand` instead
  * Added `\Cicada\Core\Maintenance\System\Command\SystemUpdatePrepareCommand`
  * Deprecated `\Cicada\Core\DevOps\System\Command\SystemUpdatePrepareCommand`, use `\Cicada\Core\Maintenance\System\Command\SystemUpdatePrepareCommand` instead
  * Added `\Cicada\Core\Maintenance\SalesChannel\Command\SalesChannelCreateCommand`
  * Deprecated `\Cicada\Core\System\SalesChannel\Command\SalesChannelCreateCommand`, use `\Cicada\Core\Maintenance\SalesChannel\Command\SalesChannelCreateCommand` instead
  * Added `\Cicada\Core\Maintenance\SalesChannel\Command\SalesChannelListCommand`
  * Deprecated `\Cicada\Core\System\SalesChannel\Command\SalesChannelListCommand`, use `\Cicada\Core\Maintenance\SalesChannel\Command\SalesChannelListCommand` instead
  * Added `\Cicada\Core\Maintenance\SalesChannel\Command\SalesChannelMaintenanceDisableCommand`
  * Deprecated `\Cicada\Core\System\SalesChannel\Command\SalesChannelMaintenanceDisableCommand`, use `\Cicada\Core\Maintenance\SalesChannel\Command\SalesChannelMaintenanceDisableCommand` instead
  * Added `\Cicada\Core\Maintenance\SalesChannel\Command\SalesChannelMaintenanceEnableCommand`
  * Deprecated `\Cicada\Core\System\SalesChannel\Command\SalesChannelMaintenanceEnableCommand`, use `\Cicada\Core\Maintenance\SalesChannel\Command\SalesChannelMaintenanceEnableCommand` instead
  * Added `\Cicada\Core\Maintenance\SalesChannel\Service\SalesChannelCreator`
  * Added `\Cicada\Core\Maintenance\System\Command\SystemConfigureShopCommand`
  * Added `\Cicada\Core\Maintenance\System\Service\DatabaseConnectionFactory`
  * Added `\Cicada\Core\Maintenance\System\Service\DatabaseInitializer`
  * Added `\Cicada\Core\Maintenance\System\Service\JwtCertificateGenerator`
  * Added `\Cicada\Core\Maintenance\System\Service\ShopConfigurator`
  * Added `\Cicada\Core\Maintenance\User\Command\UserChangePasswordCommand`
  * Deprecated `\Cicada\Core\System\User\Command\UserChangePasswordCommand`, use `\Cicada\Core\Maintenance\User\Command\UserChangePasswordCommand` instead
  * Added `\Cicada\Core\Maintenance\User\Command\UserCreateCommand`
  * Deprecated `\Cicada\Core\System\User\Command\UserCreateCommand`, use `\Cicada\Core\Maintenance\User\Command\UserCreateCommand` instead
  * Added `\Cicada\Core\Maintenance\User\Service\UserProvisioner`
  * Deprecated `\Cicada\Core\System\User\Service\UserProvisioner`, use `\Cicada\Core\Maintenance\User\Service\UserProvisioner` instead
* Changed `\Cicada\Core\Framework\Adapter\Asset\AssetInstallCommand` to additionally install assets from the Recovery bundle if it is present
* Added `\Cicada\Core\Framework\Plugin\Util\AssetService::copyRecoveryAssets()` to copy assets of the recovery bundle to the public folder
___
# Storefront
* Changed `\Cicada\Storefront\Framework\Command\SalesChannelCreateStorefrontCommand` to add `snippetSetId`-parameter and to no longer ignore the `navigationCategoryId`-parameter
___
# Upgrade Information

## Added Maintenance-Bundle

A maintenance bundle was added to have one place where CLI-commands und Utils are located, that help with the ongoing maintenance of the shop.

To load enable that bundle, you should add the following line to your `/config/bundles.php` file, because from 6.5.0 onward the bundle will not be loaded automatically anymore:
```php
return [
   ...
   Cicada\Core\Maintenance\Maintenance::class => ['all' => true],
];
```
In that refactoring we moved some CLI commands into that new bundle and deprecated the old command classes. The new commands are marked as internal, as you should not rely on the PHP interface of those commands, only on the CLI API.

Additionally we've moved the `UserProvisioner` service from the `Core/System/User` namespace, to the `Core/Maintenance/User` namespace, make sure you use the service from the new location.
Before:
```php
use Cicada\Core\System\User\Service\UserProvisioner;
```
After:
```php
use Cicada\Core\Maintenance\User\Service\UserProvisioner;
```
