---
title: Fix removal of theme data on extension uninstall
issue: NEXT-14937
author_github: @Dominik28111
---
# Core
* Added paramdter `$keepUserData` to method `Cicada\Core\Framework\Store\Services\AbstractStoreAppLifecycleService::uninstallExtension()`.
* Added parameter `$keepUserData` to method `Cicada\Core\Framework\Store\Services\StoreAppLifecycleService::uninstallExtension()`.
* Added parameter `$keepUserData` to method `Cicada\Core\Framework\App\Lifecycle\AbstractAppLifecycle::delete()`.
* Added parameter `$keepUserData` to method `Cicada\Core\Framework\App\Lifecycle\AppLifecycle::delete()`.
* Added parameter `$keepUserData` to method `Cicada\Core\Framework\App\Lifecycle\RefreshableAppDryRun::delete()`.
* Added parameter `$keepUserData` to method `Cicada\Core\Framework\App\Event\AppDeletedEvent::__construct()`.
* Added method `Cicada\Core\Framework\App\Event\AppDeletedEvent.php::keepUserData()`.
* Added option `keep-user-data` to `Cicada\Core\Framework\App\Command\UninstallAppCommand`.
* Changed method `Cicada\Core\Framework\Store\Services\ExtensionLifecycleService::uninstall` to pass `$keepUserData` to the `StoreAppLifecycleService`.
* Changed method `Cicada\Core\Framework\Store\Services\StoreAppLifecycleService::uninstallExtension` to pass `$keepUserData` to the `AppLifecycle`.
* Changed method `Cicada\Core\Framework\App\Lifecycle\AppLifecycle::delete` to pass `$keepUserData` to method `removeAppAndRole()`.
___
# Storefront
* Added method `Cicada\Storefront\Theme\ThemeLifecycleService::removeTheme()` to remove theme data.
* Added subscriber `Cicada\Storefront\Theme\Subscriber\AppLifecycleSubscriber` to handle theme data removal on `AppDeletedEvent`.
* Added method `Cicada\Storefront\Theme\Subscriber\PluginLifecycleSubscriber::pluginPostUninstall()` to handle theme data removal on `PluginPostUninstallEvent`.
