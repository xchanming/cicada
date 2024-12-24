---
title: Ensure profiler is not loaded in production mode
issue: NEXT-28902
---
# Core
* Changed `\Cicada\Core\HttpKernel` to not load the profiler when Cicada is in production mode
