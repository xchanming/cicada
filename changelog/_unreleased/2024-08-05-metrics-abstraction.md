---
title: Metrics abstraction
issue: NEXT-36658
flag: TELEMETRY_METRICS

---
# Core
* Added a new metrics abstraction layer `Cicada\Core\Framework\Telemetry` to the core. This layer allows collecting metrics from different sources and sending them to different targets. See documentation in the package folder for more details.
* Added `TELEMETRY_METRICS` feature flag to enable/disable metrics collection.
* Changed default system event dispatcher to the `MetricEventDispatcher` to listen on all events and this way support metrics collection for system events.
* Changed attributes of `Cicada\Core\Framework\Adapter\Cache\InvalidateCacheEvent` to enable emitting of `cache.invalidate` metric.
* Changed attributes of `Cicada\Core\Framework\App\Event\AppInstalledEvent` to enable emitting of `app.install` metric.
* Changed attributes of `Cicada\Core\Framework\Plugin\Event\PluginPostInstallEvent` to enable emitting of `plugin.install` metric.  
* Changed `Cicada\Core\Framework\MessageQueue\Subscriber\MessageQueueSubscriber` to listen on `onMessageReceived` event and emit `messenger.message.size` metric.
* Added `Cicada\Core\Framework\DataAbstractionLayer\Subscriber\EntityStatsSubscriber` to listen on `onEntitySearched` event and emit `dal.association.count` metric.
* Changed `Cicada\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery` and `Cicada\Core\Framework\DataAbstractionLayer\Doctrine\RetryableTransaction` to emit `database.locked` metric.
