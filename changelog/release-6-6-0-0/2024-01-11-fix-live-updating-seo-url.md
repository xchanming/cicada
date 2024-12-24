---
title: Fix live updating SEO url
issue: NEXT-33006
---
# Core
* Changed method `\Cicada\Core\Content\Seo\SeoUrlTwigFactory::createTwigEnvironment` to cast slugify string to string type
