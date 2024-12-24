---
title: Better profiling
issue: NEXT-20696
---
# Core
* Added static class `\Cicada\Core\Profiling\Profiler` to trace various functions.
* Added `Profiler::trace()` calls to multiple services to get better profiles.
* Added interface `\Cicada\Core\Profiling\Integration\ProfilerInterface` as abstraction for multiple profilers
* Added `\Cicada\Core\Profiling\Integration\Datadog` to integrate the Profiler with Datadog.
* Added `\Cicada\Core\Profiling\Integration\Stopwatch` to integrate the Profiler with the Symfony Debug Toolbar.
* Added `\Cicada\Core\Profiling\Integration\Tideways` to integrate the Profiler with Tideways.
* Deprecated `\Cicada\Core\Profiling\Checkout\SalesChannelContextServiceProfiler`, the service will be removed in v6.5.0.0, use the `Profiler` directly in your services.
* Deprecated `\Cicada\Core\Profiling\Entity\EntityAggregatorProfiler`, the service will be removed in v6.5.0.0, use the `Profiler` directly in your services.
* Deprecated `\Cicada\Core\Profiling\Entity\EntitySearcherProfiler`, the service will be removed in v6.5.0.0, use the `Profiler` directly in your services.
* Deprecated `\Cicada\Core\Profiling\Entity\EntityReaderProfiler`, the service will be removed in v6.5.0.0, use the `Profiler` directly in your services.
___
# Upgrade Information
## Better profiling integration
Cicada now supports better profiling for multiple integrations.
To activate profiling and a specific integration, add the corresponding integration name to the `cicada.profiler.integrations` parameter in your cicada.yaml file.
___
# Next Major Version Changes
## New Profiling pattern
Due to a new and better profiling pattern we removed the following services:
* `\Cicada\Core\Profiling\Checkout\SalesChannelContextServiceProfiler`
* `\Cicada\Core\Profiling\Entity\EntityAggregatorProfiler`
* `\Cicada\Core\Profiling\Entity\EntitySearcherProfiler`
* `\Cicada\Core\Profiling\Entity\EntityReaderProfiler`

You can now use the `Profiler::trace()` function to add custom traces directly from your services.
