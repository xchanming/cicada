---
title: Fix inactive category as breadcrumb
issue: NEXT-21371
author: Daniel Beyer
author_email: d.beyer@cicada.com
---
# Core
* Added a filter to `\Cicada\Core\Content\Category\Service\CategoryBreadcrumbBuilder::getProductSeoCategory` so that inactive categories are not considered anymore.
