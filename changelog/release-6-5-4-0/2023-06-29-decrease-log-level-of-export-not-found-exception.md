---
title: Decrease log level of ExportNotFoundException
issue: NEXT-28924
author: Niklas Limberg
author_email: n.limberg@cicada.com
author_github: NiklasLimberg
---
# Core
* Changed `\Cicada\Core\Content\ProductExport\SalesChannel\ExportController::logException` to allow for log_level specification
* Changed `\Cicada\Core\Content\ProductExport\SalesChannel\ExportController::index` to log the `ExportNotFoundException` with the log_level `Level::Warning`
