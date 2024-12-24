---
title: repair load custom seo url twig extensions
issue: NEXT-25509
author: Björn Herzke
author_email: bjoern.herzke@brandung.de
author_github: wrongspot
---
# Core 
* Added `Cicada\Core\Framework\Adapter\Twig\TwigVariableParserFactory`
* Changed `Cicada\Core\Content\Seo\SeoUrlGenerator::__construct` removed `TwigVariableParser` and use `TwigVariableParserFactory` instead
* Changed `Cicada\Core\Content\ProductExport\Service\ProductExportGenerator::__construct` removed `TwigVariableParser` and use `TwigVariableParserFactory` instead
* Deprecated direct usage of `Cicada\Core\Framework\Adapter\Twig\TwigVariableParser`-service use `Cicada\Core\Framework\Adapter\Twig\TwigVariableParserFactory` instead
