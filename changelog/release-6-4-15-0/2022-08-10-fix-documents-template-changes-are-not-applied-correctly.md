---
title: Fix documents template changes are not applied correctly
issue: NEXT-19784
---
# Core
* Changed method `\Cicada\Core\Checkout\Document\Twig\DocumentTemplateRenderer::render` to resolve view path after dispatching `DocumentTemplateRendererParameterEvent`
* Changed method `\Cicada\Core\Framework\Framework::getTemplatePriority` to return -1
* Changed method `\Cicada\Core\System\System::getTemplatePriority` to return -1
* Changed method `\Cicada\Core\Profiling\Profiling::getTemplatePriority` to return -2
___
# Storefront
* Added new class `\Cicada\Storefront\Theme\SalesChannelThemeLoader` to load theme of a given sales channel id
* Changed class `\Cicada\Storefront\Theme\Twig\ThemeNamespaceHierarchyBuilder` to implement `ResetInterface` and add the reset method to reset internal `$themes` property
___
# Administration
* Changed method `\Cicada\Administration\Administration::getTemplatePriority` to return -1
___
# Elasticsearch
* Changed method `\Cicada\Elasticsearch\Elasticsearch::getTemplatePriority` to return -1
