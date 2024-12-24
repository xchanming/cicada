---
title: Add filter event to modify the sitemap header
issue: NEXT-19239
author: Max
author_email: max@swk-web.com
author_github: @aragon999
---
# Core
* Added `Cicada\Core\Content\Sitemap\Event\SitemapFilterOpenTagEvent` to modify the open tag of the generated sitemap
* Added EventDispatcher for Service `Cicada\Core\Content\Sitemap\Service\SitemapHandleFactory` in `src/Core/Content/DependencyInjection/sitemap.xml` 
* Changed `Cicada\Core\Content\Sitemap\Service\SitemapHandle` to dispatch a `SitemapFilterOpenTagEvent`
* Changed `Cicada\Core\Content\Sitemap\Service\SitemapHandleFactory::__construct` for dependency Injection
* Changed `Cicada\Core\Content\Test\Sitemap\ServiceSitemapHandleTest`
