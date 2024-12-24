---
title: Improved extensibility of CMS resolvers
issue: NEXT-7505
author: Krzykawski
author_email: m.krzykawski@cicada.com
author_github: @MartinKrzykawski
---
# Core
* Changed `Cicada\Core\Content\Cms\DataResolver\CmsSlotsDataResolver` to dispatch the following event-based extensions: `CmsSlotsDataResolveExtension`, `CmsSlotsDataCollectExtension`, and `CmsSlotsDataEnrichExtension`.
* Added `Cicada\Core\Content\Cms\Extension\CmsSlotsDataResolveExtension` to allow interception of the CMS slots data resolution process.
* Added `Cicada\Core\Content\Cms\Extension\CmsSlotsDataCollectExtension` to allow interception of the CMS slots criteria collection process.
* Added `Cicada\Core\Content\Cms\Extension\CmsSlotsDataEnrichExtension` to allow interception of the CMS slots data enrichment process.
