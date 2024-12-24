---
title: Handle exception for ProductExportGenerator
issue: NEXT-30907
---
# Core
* Added new exception method `productExportNotFound` in `Cicada\Core\Content\ProductExport\ProductExportException`
* Added an alternative exception by throwing `ProductExportException::productExportNotFound()` in `generate` method of `Cicada\Core\Content\ProductExport\Service\ProductExportGenerator`
* Added an alternative exception by throwing `ProductExportException::renderProductException()` in `getAssociations` method of `Cicada\Core\Content\ProductExport\Service\ProductExportGenerator`
