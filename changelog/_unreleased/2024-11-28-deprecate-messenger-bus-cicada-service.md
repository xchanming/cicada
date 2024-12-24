---
title: Deprecate messenger.bus.cicada service
issue: NEXT-39755
---
# Core
* Deprecated the `messenger.bus.cicada` service. The functionality provided by our decorator has been moved to middleware so you can safely use `messenger.default_bus` instead.
___
# Upgrade Information
## Deprecated `messenger.bus.cicada` service
Change your usages of `messenger.bus.cicada` to `messenger.default_bus`. As long as you typed the interface `\Symfony\Component\Messenger\MessageBusInterface`, your code will work as expected.

___
# Next Major Version Changes
## Removed `messenger.bus.cicada` service
Use `messenger.default_bus` instead.
